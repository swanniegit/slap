<?php
/**
 * Output helpers. Deliberately tiny — each exists because the mockup pages
 * hand-wrote the same thing three times with three different results.
 */
declare(strict_types=1);

/** Escape for HTML text and attribute contexts. */
function slap_e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Absolute URL for a site path. */
function slap_abs(string $path): string {
    return SLAP_BASE_URL . $path;
}

/**
 * The wordmark split into the two lines the masthead lockup sets it on.
 *
 * Derived from SLAP_ORG['name'], not declared next to it. Two more constants
 * holding 'SLAP' and 'Baby Designs' would be a second spelling of a fact
 * lib/config.php already states, free to drift from it the moment either is
 * edited — and the drift would show as a masthead that disagrees with the
 * <title>, the footer and the JSON-LD.
 *
 * Split at the first space, so a one-word name yields an empty second line and
 * partials/header.php renders a single line rather than an empty <span>.
 */
function slap_wordmark(): array {
    [$lead, $rest] = array_pad(explode(' ', SLAP_ORG['name'], 2), 2, '');
    return ['lead' => $lead, 'rest' => $rest];
}

/**
 * Cache-busting version for the stylesheet bundle.
 *
 * A content hash, NOT a hand-bumped constant and NOT filemtime(). A constant is
 * a step someone forgets, and then returning visitors keep the old CSS for a
 * year while whoever tests it gets the new one. filemtime() is worse under
 * Docker: the build stamps every file with the build time, so the version would
 * change on every deploy and bust a cache that did not need busting.
 *
 * Hashing the sources costs one read of ~10 small files per request, memoised
 * per process. That is cheaper than a single wrong cache entry.
 */
function slap_css_version(): string {
    static $v = null;
    if ($v !== null) return $v;

    $h = hash_init('xxh128');
    foreach (slap_css_parts() as $file) {
        hash_update($h, (string)@file_get_contents($file));
    }
    return $v = substr(hash_final($h), 0, 12);
}

/**
 * The stylesheet, in cascade order. This list is the only place the order is
 * written; assets/css.php concatenates exactly these and nothing else, so a new
 * file that is not added here simply does not ship — a loud failure, not a
 * mysterious one.
 */
function slap_css_parts(): array {
    static $parts = null;
    if ($parts !== null) return $parts;

    $order = [
        'tokens',      // custom properties only
        'base',        // element defaults
        'layout',      // wrap, band, grid, section rhythm
        'seam',        // THE signature: stitch lines and panel edges
        'type',        // headings, lead, label, patchwork headline
        'button',
        'nav',
        'hero',
        'facts',
        'panel',       // the two-route panels
        'bear',        // gallery card + care label
        'form',
        'prose',       // long-form running text, privacy page only so far
        'steps',       // the numbered list beside the enquiry form
        'footer',
    ];
    return $parts = array_map(
        fn(string $n): string => SLAP_ROOT . '/assets/css/' . $n . '.css',
        $order
    );
}

/** Versioned stylesheet URL. .htaccess rewrites the hash back to assets/css.php. */
function slap_css_url(): string {
    return '/assets/site.' . slap_css_version() . '.css';
}

/**
 * Content-hashed URL for a file in assets/brand/.
 *
 * The same trick as slap_css_url(), for the same reason and after the same bug.
 * The mark and the touch icon are the one kind of asset this site has that
 * changes WITHOUT changing its filename: a photo of a new bear arrives as a new
 * file, but redrawing the logo rewrites mark.svg in place. Served at a fixed URL
 * they were cached as immutable for a year, so the bear that replaced the gold S
 * patch never reached a single returning visitor — and could not, because a
 * browser holding an immutable entry does not revalidate it. Shortening the
 * max-age fixed that going forward but reaches nobody already holding one; only
 * a different URL does, and that is this.
 *
 * .htaccess rewrites the hash back out again, so the bytes are still served from
 * disk by Apache and no PHP runs per request. The unhashed path keeps working
 * for site.webmanifest and for anything that guesses at /assets/brand/mark.svg.
 */
function slap_brand_url(string $file): string {
    static $urls = [];
    if (isset($urls[$file])) return $urls[$file];

    $hash = substr(hash('xxh128', (string)@file_get_contents(SLAP_ROOT . '/assets/brand/' . $file)), 0, 12);
    $dot  = strrpos($file, '.');

    return $urls[$file] = '/assets/brand/' . substr($file, 0, $dot) . '.' . $hash . substr($file, $dot);
}

/** Per-request CSP nonce. The JSON-LD block needs it too — script-src governs it. */
function slap_nonce(): string {
    static $n = null;
    return $n ??= base64_encode(random_bytes(12));
}

/**
 * <img> that always carries width and height.
 *
 * The mockups omitted both on every image, which guarantees layout shift on a
 * page that is mostly photographs. getimagesize() is core PHP, so the numbers
 * are read from the file and cannot drift the way hardcoded ones do.
 *
 * There is deliberately no `sizes`: sizes only means anything alongside a
 * `srcset` of width descriptors, and there is one file per bear. An unpaired
 * sizes attribute is ignored by every browser while reading like a working
 * responsive-image setup, which is how a site ends up believing it has one.
 */
function slap_img(string $src, string $alt, array $opt = []): string {
    static $dims = [];
    $dims[$src] ??= @getimagesize(SLAP_ROOT . $src) ?: [null, null];
    [$w, $h] = $dims[$src];

    $eager = !empty($opt['eager']);
    $attr  = $w !== null ? ' width="' . (int)$w . '" height="' . (int)$h . '"' : '';
    $attr .= ' loading="' . ($eager ? 'eager' : 'lazy') . '" decoding="async"';
    if ($eager)                $attr .= ' fetchpriority="high"';
    if (!empty($opt['class'])) $attr .= ' class="' . slap_e($opt['class']) . '"';

    return '<img src="' . slap_e($src) . '" alt="' . slap_e($alt) . '"' . $attr . '>';
}

/**
 * Render a partial with an explicit variable set, in its own scope.
 *
 * Plain `require` shares the caller's scope, which is how a loop variable in
 * one partial silently overwrites another's. Everything a partial needs arrives
 * through $vars and nothing else is visible, so a partial can be moved or
 * reused without reading the page that calls it.
 */
function slap_partial(string $name, array $vars = []): void
{
    (static function (string $__file, array $__vars): void {
        extract($__vars, EXTR_SKIP);
        require $__file;
    })(SLAP_ROOT . '/partials/' . $name . '.php', $vars);
}
