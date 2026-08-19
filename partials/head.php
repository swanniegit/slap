<?php
/**
 * <!doctype> to </head>. Reads only the current manifest entry, so the title,
 * description, canonical, Open Graph and Twitter data cannot disagree.
 *
 * Fixes carried over from the mockups, where <head> was three <style> blocks
 * and nothing else: no title, no description, no canonical, no social preview.
 */
declare(strict_types=1);

$p       = slap_current();
$ogImage = slap_abs($p['og_image']);
[$ogW, $ogH] = @getimagesize(SLAP_ROOT . $p['og_image']) ?: [1200, 630];

slap_send_security_headers();
?><!doctype html>
<html lang="en-ZA">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?= slap_e($p['title']) ?></title>
<meta name="description" content="<?= slap_e($p['description']) ?>">
<meta name="robots" content="<?= slap_e($p['robots']) ?>">
<link rel="canonical" href="<?= slap_e($p['canonical']) ?>">
<?php if (SLAP_GSC_TOKEN !== ''): ?>
<meta name="google-site-verification" content="<?= slap_e(SLAP_GSC_TOKEN) ?>">
<?php endif; ?>

<meta property="og:type" content="website">
<meta property="og:url" content="<?= slap_e($p['canonical']) ?>">
<meta property="og:title" content="<?= slap_e($p['title']) ?>">
<meta property="og:description" content="<?= slap_e($p['description']) ?>">
<meta property="og:image" content="<?= slap_e($ogImage) ?>">
<meta property="og:image:width" content="<?= (int)$ogW ?>">
<meta property="og:image:height" content="<?= (int)$ogH ?>">
<meta property="og:image:alt" content="<?= slap_e($p['og_image_alt'] ?? $p['title']) ?>">
<meta property="og:locale" content="en_ZA">
<meta property="og:site_name" content="<?= slap_e(SLAP_ORG['name']) ?>">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= slap_e($p['title']) ?>">
<meta name="twitter:description" content="<?= slap_e($p['description']) ?>">
<meta name="twitter:image" content="<?= slap_e($ogImage) ?>">
<meta name="twitter:image:alt" content="<?= slap_e($p['og_image_alt'] ?? $p['title']) ?>">

<meta name="theme-color" content="#FFF8EE">
<link rel="icon" href="/assets/brand/mark.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/assets/brand/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;800&amp;family=Karla:wght@400;500;600;700&amp;display=swap">
<link rel="stylesheet" href="<?= slap_e(slap_css_url()) ?>">

<?php require SLAP_ROOT . '/partials/schema.php'; ?>
<?php require SLAP_ROOT . '/partials/analytics.php'; ?>
</head>
