<?php
/**
 * Everything derived from the manifest: the current page, its canonical URL,
 * the JSON-LD graph and the sitemap.
 *
 * Nothing here reads a URL from anywhere but lib/pages.php, which is what stops
 * the canonical, the og:url and the sitemap entry from ever disagreeing.
 */
declare(strict_types=1);

/** The manifest, loaded once, with canonical and path folded into each entry. */
function slap_pages(): array
{
    static $pages = null;
    if ($pages !== null) {
        return $pages;
    }

    $pages = require SLAP_ROOT . '/lib/pages.php';
    foreach ($pages as $path => &$p) {
        $p['path']      = $path;
        $p['canonical'] = slap_abs($path);
    }
    unset($p);
    return $pages;
}

/**
 * Declares which manifest entry is being rendered. Every page calls this once,
 * immediately after bootstrap, before any output.
 */
function slap_page(string $path): void
{
    $pages = slap_pages();
    if (!isset($pages[$path])) {
        // A page whose own manifest key is wrong would otherwise render with an
        // empty <title> and a canonical pointing at the domain root — an SEO
        // failure that looks fine in a browser. Fail loudly instead.
        throw new RuntimeException("No manifest entry for '$path'. Add it to lib/pages.php.");
    }
    $GLOBALS['SLAP_PAGE'] = $pages[$path];
}

/** The entry set by slap_page(). */
function slap_current(): array
{
    return $GLOBALS['SLAP_PAGE'] ?? throw new RuntimeException('slap_page() was not called before output.');
}

/** Short nav/breadcrumb label, falling back to the title. */
function slap_short_label(array $p): string
{
    return $p['short_label'] ?? $p['title'];
}

/** Breadcrumb trail for the current page, root first. Empty on the home page. */
function slap_trail(array $p): array
{
    $pages = slap_pages();
    $trail = [];
    for ($cur = $p; $cur !== null; $cur = $cur['parent'] === null ? null : ($pages[$cur['parent']] ?? null)) {
        array_unshift($trail, $cur);
    }
    return count($trail) > 1 ? $trail : [];
}

/**
 * One @id-linked JSON-LD graph rather than several loose blocks.
 *
 * Loose blocks are how the same business ends up described as three unrelated
 * entities. Linking Organization, WebSite, WebPage and BreadcrumbList by @id
 * tells a crawler they are one thing.
 */
function slap_schema_graph(array $p): array
{
    $o    = SLAP_ORG;
    $org  = slap_abs('/') . '#org';
    $site = slap_abs('/') . '#site';

    $sameAs = array_values(array_filter([$o['facebook'], $o['instagram']]));

    $graph = [
        array_filter([
            '@type'       => 'Organization',
            '@id'         => $org,
            'name'        => $o['name'],
            'slogan'      => $o['tagline'],
            'url'         => slap_abs('/'),
            'founder'     => array_map(
                static fn(string $n): array => ['@type' => 'Person', 'name' => $n],
                $o['makers']
            ),
            'email'       => $o['email'] ?: null,
            'telephone'   => $o['phone'] ?: null,
            'sameAs'      => $sameAs ?: null,
            'areaServed'  => ['@type' => 'Country', 'name' => $o['region']],
            'description' => 'Handmade keepsake bears sewn from clothes that already mean something.',
        ]),
        [
            '@type'      => 'WebSite',
            '@id'        => $site,
            'url'        => slap_abs('/'),
            'name'       => $o['name'],
            'publisher'  => ['@id' => $org],
            'inLanguage' => 'en-ZA',
        ],
        [
            '@type'       => 'WebPage',
            '@id'         => $p['canonical'],
            'url'         => $p['canonical'],
            'name'        => $p['title'],
            'description' => $p['description'],
            'isPartOf'    => ['@id' => $site],
            'about'       => ['@id' => $org],
            'dateModified' => $p['updated'],
        ],
    ];

    $trail = slap_trail($p);
    if ($trail) {
        $graph[] = [
            '@type'           => 'BreadcrumbList',
            '@id'             => $p['canonical'] . '#breadcrumb',
            'itemListElement' => array_map(
                static fn(int $i, array $step): array => [
                    '@type'    => 'ListItem',
                    'position' => $i + 1,
                    'name'     => slap_short_label($step),
                    'item'     => $step['canonical'],
                ],
                array_keys($trail),
                $trail
            ),
        ];
    }

    return ['@context' => 'https://schema.org', '@graph' => $graph];
}

/** Manifest entries that belong in sitemap.xml, in manifest order. */
function slap_sitemap_entries(): array
{
    return array_values(array_filter(
        slap_pages(),
        static fn(array $p): bool => !empty($p['sitemap']) && !str_contains($p['robots'], 'noindex')
    ));
}
