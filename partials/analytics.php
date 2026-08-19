<?php
/**
 * Google Analytics 4.
 *
 * Renders nothing at all when SLAP_GA4_ID is empty, which is the state the site
 * ships in. That matters for more than tidiness: an empty measurement ID would
 * otherwise load gtag.js on every page, set cookies, and report to nowhere —
 * all of the cost and none of the data.
 *
 * The inline block carries the CSP nonce because lib/bootstrap.php sets
 * script-src to 'self' plus a nonce; without it the browser blocks this script
 * and the only sign is a console entry nobody is looking at.
 *
 * anonymize_ip is on. GA4 anonymises by default, but stating it here is what a
 * POPIA-conscious privacy note can actually point at.
 */
declare(strict_types=1);

if (SLAP_GA4_ID === '') {
    return;
}
?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= slap_e(SLAP_GA4_ID) ?>"></script>
<script nonce="<?= slap_e(slap_nonce()) ?>">
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?= slap_e(SLAP_GA4_ID) ?>', { anonymize_ip: true });
</script>
