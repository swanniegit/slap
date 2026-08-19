<?php
/**
 * site.webmanifest, generated from the declarations the rest of the site uses.
 *
 * .htaccess rewrites /site.webmanifest here. It was static JSON, and every one
 * of its fields was a second spelling of a fact stated somewhere else: the name,
 * the short name, the description, the locale, the two colours, the start URL
 * and both icon paths. One had already drifted — its description promised
 * "memory bears ... and character bears made new" while the JSON-LD promised
 * "keepsake bears", and both were live. That is the whole argument for this file.
 *
 * Generating it also puts the icons on their content-hashed URLs, which the
 * static version could not use: those URLs only exist once slap_brand_url() has
 * hashed the file, and JSON cannot call a function.
 *
 * Deliberately NOT calling slap_send_security_headers(): it ends by setting
 * Cache-Control to must-revalidate, which would undo the hour set below. That
 * is the same reason sitemap.php does not call it either.
 */
declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';

$pages = slap_pages();
$home  = $pages['/']['path'] ?? '/';
$paper = slap_css_token('paper');

/* Real dimensions, read from the file. The static manifest asserted 180x180 by
   hand, which was true only for as long as nobody re-rendered the icon. */
[$iconW, $iconH] = @getimagesize(SLAP_ROOT . '/assets/brand/apple-touch-icon.png') ?: [180, 180];

$manifest = [
    'name'       => SLAP_ORG['name'],
    /* The first line of the masthead lockup, so the home-screen label and the
       logo cannot disagree about where the name is cut. */
    'short_name' => slap_wordmark()['lead'],
    'description' => SLAP_ORG['description'],
    'lang'       => SLAP_ORG['locale'],
    'start_url'  => $home,
    'scope'      => $home,
    'display'    => 'browser',
    'theme_color'      => $paper,
    'background_color' => $paper,
    'icons' => [
        [
            'src'     => slap_brand_url('mark.svg'),
            'type'    => 'image/svg+xml',
            'sizes'   => 'any',
            'purpose' => 'any',
        ],
        [
            'src'     => slap_brand_url('apple-touch-icon.png'),
            'type'    => 'image/png',
            'sizes'   => $iconW . 'x' . $iconH,
            'purpose' => 'any',
        ],
    ],
];

header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

echo json_encode(
    $manifest,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_PRETTY_PRINT
), "\n";
