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
    $links = [['path' => '/', 'label' => slap_short_label($pages['/'])]];

    foreach (slap_nav_items() as $item) {
        $links[] = ['path' => $item['path'], 'label' => $item['label']];
    }
    foreach ($pages as $path => $p) {
        if (!empty($p['footer'])) {
            $links[] = ['path' => $path, 'label' => slap_short_label($p)];
        }
    }
    return $links;
}
