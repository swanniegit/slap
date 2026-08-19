<?php
/**
 * CI guard for lib/pages.php. Runs in the `verify` job and is the required
 * check for branch protection.
 *
 * lib/pages.php is the single source of every URL on the site — nav, canonical,
 * Open Graph, JSON-LD and sitemap.xml are all derived from it. That is what
 * stops them drifting apart, but it also means a mistake in the manifest is
 * invisible in a browser and only shows up in Search Console weeks later: a
 * canonical pointing at nothing, two pages fighting over the same title, a
 * noindex page advertised in the sitemap. Every check below is one of those
 * failures made loud at push time instead.
 *
 * Exit code is the whole interface: 0 with "manifest OK", non-zero with one
 * line per problem.
 */
declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';

/** Paths that are never a page, so the orphan scan must not claim them. */
const CHECK_SKIP_DIRS  = ['lib', 'partials', 'scripts', 'vendor', 'data', 'node_modules'];

/**
 * PHP under the webroot that is deliberately not a manifest page. css.php is an
 * asset endpoint reached through a rewrite; sitemap.php generates sitemap.xml
 * and is itself never a page a person lands on.
 */
const CHECK_SKIP_FILES = ['assets/css.php', 'sitemap.php', 'robots.php',
                          'site-webmanifest.php'];

$problems = [];
$pages    = slap_pages();

/** Absolute path for a site-relative path, in the repo's own layout. */
$onDisk = static fn(string $rel): string => SLAP_ROOT . '/' . ltrim($rel, '/');

// ---------------------------------------------------------------------------
// 1. Every entry names a file that exists.
//    A manifest entry with no file is a 404 that the sitemap tells Google to
//    go and fetch.
foreach ($pages as $path => $p) {
    $file = (string)($p['file'] ?? '');
    if ($file === '') {
        $problems[] = "$path has no 'file'";
    } elseif (!is_file($onDisk($file))) {
        $problems[] = "$path names a file that is not on disk: $file";
    }
}

// ---------------------------------------------------------------------------
// 2. Every parent resolves to another entry.
//    A dangling parent silently truncates the breadcrumb trail and the
//    BreadcrumbList JSON-LD, which is worse than having no breadcrumbs at all.
foreach ($pages as $path => $p) {
    $parent = $p['parent'] ?? null;
    if ($parent !== null && !isset($pages[$parent])) {
        $problems[] = "$path has a parent that is not a manifest key: '$parent'";
    }
}

// ---------------------------------------------------------------------------
// 3. No two entries share a title.
//    Duplicate titles are an SEO own-goal: Google picks one of the two pages to
//    show and treats the other as a near-duplicate of it.
$seenTitles = [];
foreach ($pages as $path => $p) {
    $title = (string)($p['title'] ?? '');
    if ($title === '') {
        $problems[] = "$path has no title";
        continue;
    }
    if (isset($seenTitles[$title])) {
        $problems[] = "$path and {$seenTitles[$title]} share the title: \"$title\"";
    }
    $seenTitles[$title] = $path;
}

// ---------------------------------------------------------------------------
// 4. A noindex page is never in the sitemap.
//    The two instructions contradict each other, and a crawler that is told to
//    fetch a page and then told to forget it wastes crawl budget on every pass.
foreach ($pages as $path => $p) {
    if (!empty($p['sitemap']) && str_contains((string)($p['robots'] ?? ''), 'noindex')) {
        $problems[] = "$path is noindex but still has a 'sitemap' block";
    }
}

// ---------------------------------------------------------------------------
// 5. Every og_image exists.
//    A missing one is not a broken image on the page — it is a WhatsApp or
//    Facebook share that renders as a grey box, seen by everyone except the
//    person who shared it.
foreach ($pages as $path => $p) {
    $og = (string)($p['og_image'] ?? '');
    if ($og === '') {
        $problems[] = "$path has no og_image";
    } elseif (!is_file($onDisk($og))) {
        $problems[] = "$path has an og_image that is not on disk: $og";
    }
}

// ---------------------------------------------------------------------------
// 6. Every page file declares its own manifest key.
//    slap_page('/wrong/') renders a real-looking page with another page's title
//    and canonical. Nothing throws, nothing looks wrong in a browser, and two
//    URLs end up claiming to be the same document.
foreach ($pages as $path => $p) {
    $file = (string)($p['file'] ?? '');
    if ($file === '' || !is_file($onDisk($file))) {
        continue;   // already reported by check 1
    }
    $src = (string)file_get_contents($onDisk($file));
    if (!preg_match_all("~slap_page\(\s*'([^']*)'\s*\)~", $src, $m)) {
        $problems[] = "$file never calls slap_page() — it will throw on first render";
    } elseif (!in_array($path, $m[1], true)) {
        $problems[] = "$file calls slap_page('{$m[1][0]}') but the manifest keys it as '$path'";
    }
}

// ---------------------------------------------------------------------------
// 7. Every stylesheet part exists.
//    slap_css_parts() is the whole build. A name in that list with no file
//    behind it ships a site missing, say, all of its nav styles — and because
//    assets/css.php skips what it cannot read, the page still renders 200.
foreach (slap_css_parts() as $part) {
    if (!is_file($part)) {
        $problems[] = 'listed in slap_css_parts() but not on disk: assets/css/' . basename($part);
    }
}

// ---------------------------------------------------------------------------
// 8. No orphan pages: PHP under the webroot that no manifest entry claims.
//    An unclaimed page is reachable, indexable and outside every guarantee the
//    manifest makes — no canonical, no title, not in the sitemap. This is how
//    an old draft stays live for a year.
$claimed = [];
foreach ($pages as $p) {
    if (!empty($p['file'])) {
        $claimed[ltrim((string)$p['file'], '/')] = true;
    }
}
foreach (CHECK_SKIP_FILES as $rel) {
    $claimed[$rel] = true;
}

$walker = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator(SLAP_ROOT, FilesystemIterator::SKIP_DOTS),
        static function (SplFileInfo $info): bool {
            $name = $info->getFilename();
            if ($name[0] === '.') {
                return false;   // .git, .github — not served
            }
            return !$info->isDir() || !in_array($name, CHECK_SKIP_DIRS, true);
        }
    )
);
foreach ($walker as $info) {
    if (strtolower($info->getExtension()) !== 'php') {
        continue;
    }
    $rel = str_replace('\\', '/', substr($info->getPathname(), strlen(SLAP_ROOT) + 1));
    if (!isset($claimed[$rel])) {
        $problems[] = "on disk but no manifest entry claims it: $rel";
    }
}

// --- 'updated' must exist and be parseable -----------------------------------
// privacy/index.php renders it as a date, seo.php puts it in the JSON-LD and
// sitemap.php emits it as <lastmod>. strtotime() returns false on a bad value,
// and false into date() under strict_types is a TypeError — a blank 500 with
// display_errors off. Google also silently drops a sitemap entry whose lastmod
// is not a valid W3C date, which is invisible until traffic does not arrive.
foreach ($pages as $path => $p) {
    if (!isset($p['updated'])) {
        $problems[] = "$path has no 'updated' date";
        continue;
    }
    if (!is_string($p['updated']) || strtotime($p['updated']) === false) {
        $problems[] = "$path has an unparseable 'updated': " . var_export($p['updated'], true);
        continue;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $p['updated'])) {
        $problems[] = "$path 'updated' must be YYYY-MM-DD, got: {$p['updated']}";
    }
}

// --- 'footer' must be a real boolean flag ------------------------------------
// It is read as !empty($p['footer']) by slap_footer_links(), so a misspelled key
// fails silently: the link simply never appears, and the only symptom is a
// privacy notice with no route to it from anywhere on the site.
$knownKeys = ['file', 'title', 'description', 'short_label', 'robots', 'og_image',
              'og_image_alt', 'parent', 'nav', 'footer', 'sitemap', 'updated',
              'path', 'canonical'];
foreach ($pages as $path => $p) {
    if (array_key_exists('footer', $p) && !is_bool($p['footer'])) {
        $problems[] = "$path 'footer' must be true or false, got: " . var_export($p['footer'], true);
    }
    foreach (array_keys($p) as $key) {
        if (!in_array($key, $knownKeys, true)) {
            $problems[] = "$path has unknown manifest key '$key' — typo, or add it to \$knownKeys";
        }
    }
}

// --- at least one page must reach the footer ---------------------------------
if (array_filter($pages, static fn(array $p): bool => !empty($p['footer'])) === []) {
    $problems[] = "no page is marked 'footer' — the privacy notice would be unreachable";
}

// --- contact details must be well formed, and unfilled ones must be visible --
// lib/config.php used to claim this file "can fail CI while any of them are
// still empty in a production build". It did not: nothing here referenced
// SLAP_ORG at all. A comment describing a gate that does not exist is worse
// than no comment, because the next person trusts it.
//
// So this is the gate, and it draws the line where the line can honestly be
// drawn. EMPTY is a business fact — SLAP has not opened a Facebook page — and
// every consumer already degrades gracefully: partials/footer.php filters out
// falsy channels, lib/seo.php filters sameAs. Failing on empty would red every
// push until someone signs up for something. MALFORMED is a defect: a number
// that is not E.164 makes a tel: link that silently does nothing, and a wa.me
// URL of the wrong shape is precisely the dead WhatsApp link lib/config.php
// warns about. That fails.
$contactRules = [
    'email'     => [FILTER_VALIDATE_EMAIL, 'an email address'],
    'phone'     => ['/^\+[1-9]\d{7,14}$/', 'E.164, e.g. +27821234567'],
    'whatsapp'  => ['#^https://wa\.me/[1-9]\d{7,14}$#', 'https://wa.me/27821234567'],
    'facebook'  => [FILTER_VALIDATE_URL, 'a full https:// URL'],
    'instagram' => [FILTER_VALIDATE_URL, 'a full https:// URL'],
];

$unfilled = [];
foreach ($contactRules as $key => [$rule, $shape]) {
    $value = SLAP_ORG[$key];
    if ($value === '') {
        $unfilled[] = $key;
        continue;
    }
    $ok = is_int($rule)
        ? filter_var($value, $rule) !== false
        : preg_match($rule, $value) === 1;
    if (!$ok) {
        $problems[] = "SLAP_ORG['$key'] is not $shape: " . var_export($value, true);
    }
}

// The phone is declared twice: once machine-readable, once as a person writes
// it. partials/footer.php prints one and dials the other, so if they drift the
// footer shows a number that is not the number it calls.
if (SLAP_ORG['phone'] !== '' && SLAP_ORG['phone_display'] !== '') {
    $digits = static fn(string $s): string => preg_replace('/\D/', '', $s);
    $local  = ltrim($digits(SLAP_ORG['phone_display']), '0');
    if ($local === '' || !str_ends_with($digits(SLAP_ORG['phone']), $local)) {
        $problems[] = sprintf("SLAP_ORG['phone_display'] (%s) is not the same number as ['phone'] (%s)",
            SLAP_ORG['phone_display'], SLAP_ORG['phone']);
    }
}

// ---------------------------------------------------------------------------
foreach ($problems as $problem) {
    fwrite(STDERR, "  ERROR: $problem\n");
}

if ($problems === []) {
    printf("manifest OK — %d pages, %d in the sitemap, %d stylesheet parts\n",
        count($pages), count(slap_sitemap_entries()), count(slap_css_parts()));
    if ($unfilled !== []) {
        // Printed, not failed: see the contact block above. It is here so that
        // "still not supplied" shows up on every CI run, rather than resting in
        // a TODO comment nobody opens.
        printf("  awaiting SLAP: %s\n", implode(', ', $unfilled));
    }
    exit(0);
}

fwrite(STDERR, sprintf("\n%d problem(s) in the manifest\n", count($problems)));
exit(1);
