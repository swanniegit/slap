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
