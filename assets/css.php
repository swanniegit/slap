<?php
/**
 * The stylesheet bundle.
 *
 * The CSS is written as a dozen small files, one concern each, and served as
 * one request. There is no build step and no committed bundle to fall out of
 * date — this reads the parts listed in slap_css_parts() and concatenates them
 * in that order.
 *
 * .htaccess rewrites /assets/site.<hash>.css here. The hash is derived from the
 * contents of the parts, so the URL changes exactly when the CSS changes: no
 * constant to remember to bump, and no possibility of a returning visitor being
 * served a year-old stylesheet because someone forgot.
 *
 * A request whose hash does not match what is on disk is answered with the
 * current CSS but without the immutable cache header — that URL is stale, and
 * caching it for a year would pin the mistake.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/lib/bootstrap.php';

$current   = slap_css_version();
$requested = (string)($_GET['v'] ?? '');
$fresh     = hash_equals($current, $requested);

header('Content-Type: text/css; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('ETag: "' . $current . '"');
header($fresh
    ? 'Cache-Control: public, max-age=31536000, immutable'
    : 'Cache-Control: public, max-age=0, must-revalidate');

if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === '"' . $current . '"') {
    http_response_code(304);
    exit;
}

foreach (slap_css_parts() as $part) {
    $css = @file_get_contents($part);
    if ($css === false) {
        // A missing part would silently ship a site with, say, no nav styles.
        // Say which file, in the response, where it cannot be missed.
        echo "/* MISSING: " . basename($part) . " — listed in slap_css_parts() but not on disk */\n";
        continue;
    }
    echo "/* === " . basename($part) . " === */\n" . $css . "\n";
}
