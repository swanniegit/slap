<?php
/**
 * Navigation, derived from the manifest.
 *
 * This replaces three hand-maintained copies of the same nav. They had already
 * drifted: Home and Gallery gave the Enquire button a coral ground, Enquiry
 * gave it grape, and only the active page carried an underline — with no
 * aria-current anywhere, so a screen reader could not tell where it was.
 */
declare(strict_types=1);

/** Nav items in manifest-declared order. */
function slap_nav_items(): array
{
    $items = [];
    foreach (slap_pages() as $path => $p) {
        if (empty($p['nav'])) {
            continue;
        }
        $items[] = [
            'path'  => $path,
            'label' => $p['nav']['label'] ?? slap_short_label($p),
            'order' => $p['nav']['order'] ?? 99,
            'cta'   => !empty($p['nav']['cta']),
        ];
    }
    usort($items, static fn(array $a, array $b): int => $a['order'] <=> $b['order']);
    return $items;
}

/** True when $path is the page being rendered. Drives aria-current. */
function slap_is_current(string $path): bool
{
    return (slap_current()['path'] ?? null) === $path;
}

/**
 * Links for the footer: Home, then the masthead items, then anything the
 * manifest marks 'footer' (the privacy page).
 *
 * Built here rather than typed into partials/footer.php so that adding a page
 * is still a single manifest edit — the same promise the masthead nav makes.
 * Home is included explicitly because the brand mark is its link in the
 * masthead, so it has no 'nav' entry of its own.
 */
function slap_footer_links(): array
{
    $pages = slap_pages();
    $links = [];

    // Home first. Taken from the manifest entry rather than typed as '/',
    // because lib/pages.php's whole invariant is that the key is the only place
    // a URL is written. Guarded: if the home entry is ever rekeyed, an
    // unguarded lookup would pass null into slap_short_label(array) and fatal
    // the footer of EVERY page, not just this one.
    if (isset($pages['/'])) {
        $links[] = ['path' => $pages['/']['path'], 'label' => slap_short_label($pages['/'])];
    }

    foreach (slap_nav_items() as $item) {
        $links[] = ['path' => $item['path'], 'label' => $item['label']];
    }
    foreach ($pages as $p) {
        if (!empty($p['footer'])) {
            $links[] = ['path' => $p['path'], 'label' => slap_short_label($p)];
        }
    }

    // A page marked both 'nav' and 'footer' would otherwise be listed twice.
    // Keyed by path so the first label wins, which is the masthead's.
    $unique = [];
    foreach ($links as $link) {
        $unique[$link['path']] ??= $link;
    }
    return array_values($unique);
}
