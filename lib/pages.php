<?php
/**
 * THE PAGE MANIFEST — one entry per page, keyed by its canonical path.
 *
 * The key is the only place a URL is written. Nav, <head> meta, canonical,
 * Open Graph, JSON-LD and sitemap.xml are all derived from this array, so they
 * cannot drift apart the way the three mockup pages already had: each carried
 * its own copy of the nav and footer, and the Enquiry page's nav had already
 * lost the coral CTA colour the other two used.
 *
 * To add a page: add an entry, create the file it names. Everything else
 * follows. scripts/check-manifest.php fails CI on a missing file, a dangling
 * parent, a duplicate title, a noindex page in the sitemap, or a page on disk
 * that this manifest does not know about.
 *
 * 'updated' is authoritative lastmod, written by hand. Deliberately NOT
 * filemtime(): Docker stamps every file at build time, so filemtime would tell
 * Google "everything changed today" on every deploy until it stopped listening.
 */
declare(strict_types=1);

return [
    '/' => [
        'file'        => 'index.php',
        'title'       => 'SLAP Baby Designs | Handmade memory bears, South Africa',
        // Home has no masthead nav entry (the brand mark is the link), but the
        // footer list and the JSON-LD breadcrumb both name it, and both used to
        // spell "Home" out for themselves.
        'short_label' => 'Home',
        'description' => 'Keepsake bears sewn by hand from clothes that already mean something — a rugby jersey, a first cot sheet, a uniform. Made one at a time by two sisters in South Africa.',
        'robots'      => 'index,follow',
        'og_image'    => '/assets/img/bears/stormers.jpeg',
        'og_image_alt'=> 'A teddy bear sewn from a Stormers rugby jersey, the Vodacom Super Rugby badge kept on its chest',
        'parent'      => null,
        'nav'         => null,
        'sitemap'     => ['changefreq' => 'monthly', 'priority' => 1.0],
        'updated'     => '2026-08-19',
    ],

    '/gallery/' => [
        'file'        => 'gallery/index.php',
        'title'       => 'Gallery | Bears we have finished — SLAP Baby Designs',
        'description' => 'Every bear we have sewn so far: supporters\' kit from the Stormers, Bulls and Sharks, a first cot sheet, a nurse and a theatre bear. Each one is a one-off.',
        'short_label' => 'Gallery',
        'robots'      => 'index,follow',
        'og_image'    => '/assets/img/bears/pooh-print.jpeg',
        'og_image_alt'=> 'A pale blue teddy bear sewn from a nursery cot sheet, wearing a yellow sun hat',
        'parent'      => '/',
        'nav'         => ['label' => 'Gallery', 'order' => 10],
        'sitemap'     => ['changefreq' => 'weekly', 'priority' => 0.9],
        'updated'     => '2026-08-19',
    ],

    '/enquiry/' => [
        'file'        => 'enquiry/index.php',
        'title'       => 'Enquire | Send us your fabric — SLAP Baby Designs',
        'description' => 'Tell us what you have and what you would like made. We come back with what is possible, how long it takes and what it costs.',
        'short_label' => 'Enquire',
        'robots'      => 'index,follow',
        'og_image'    => '/assets/img/bears/nurse.jpeg',
        'og_image_alt'=> 'A brown teddy bear in a white nurse\'s pinafore and cap',
        'parent'      => '/',
        'nav'         => ['label' => 'Enquire', 'order' => 20, 'cta' => true],
        'sitemap'     => ['changefreq' => 'yearly', 'priority' => 0.8],
        'updated'     => '2026-08-19',
    ],

    '/privacy/' => [
        'file'        => 'privacy/index.php',
        'title'       => 'Privacy | SLAP Baby Designs',
        'description' => 'What SLAP Baby Designs does with the details you send through the enquiry form, how long they are kept, and how to have them deleted.',
        'short_label' => 'Privacy',
        'robots'      => 'index,follow',
        'og_image'    => '/assets/img/bears/corduroy-pinafore.jpeg',
        'og_image_alt'=> 'A brown corduroy teddy bear in a yellow pinafore',
        'parent'      => '/',
        'nav'         => null,
        // Not in the masthead — it belongs at the bottom of the page, where
        // people look for it. 'footer' is what puts it there; see lib/nav.php.
        'footer'      => true,
        'sitemap'     => ['changefreq' => 'yearly', 'priority' => 0.2],
        'updated'     => '2026-08-19',
    ],

    // Reached only by Apache's ErrorDocument, so it has no nav entry and must
    // never appear in the sitemap. It is in the manifest so that it still gets
    // a real <head> instead of the bare Apache page.
    '/404' => [
        'file'        => '404.php',
        'title'       => 'Page not found | SLAP Baby Designs',
        'description' => 'That page is not here.',
        'robots'      => 'noindex,follow',
        'og_image'    => '/assets/img/bears/pooh-print.jpeg',
        'parent'      => '/',
        'nav'         => null,
        'sitemap'     => null,
        'updated'     => '2026-08-19',
    ],
];
