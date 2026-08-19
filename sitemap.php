<?php
/**
 * sitemap.xml, generated from the manifest.
 *
 * .htaccess rewrites /sitemap.xml here. Nothing is listed by hand, so a new
 * page cannot be added to the site and forgotten here, and a page marked
 * noindex cannot be advertised to Google by a sitemap nobody re-read.
 */
declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach (slap_sitemap_entries() as $p): ?>
  <url>
    <loc><?= slap_e($p['canonical']) ?></loc>
    <lastmod><?= slap_e($p['updated']) ?></lastmod>
    <changefreq><?= slap_e($p['sitemap']['changefreq']) ?></changefreq>
    <priority><?= number_format((float)$p['sitemap']['priority'], 1) ?></priority>
  </url>
<?php endforeach; ?>
</urlset>
