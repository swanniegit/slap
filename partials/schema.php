<?php
/**
 * The JSON-LD graph. One block, built by slap_schema_graph(), carrying the CSP
 * nonce because script-src governs application/ld+json too.
 *
 * JSON_UNESCAPED_SLASHES keeps the URLs readable in view-source; JSON_HEX_TAG
 * makes a stray "</script>" inside any string impossible to break out of.
 */
declare(strict_types=1);
?>
<script type="application/ld+json" nonce="<?= slap_e(slap_nonce()) ?>">
<?= json_encode(
    slap_schema_graph(slap_current()),
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_PRETTY_PRINT
) ?>
</script>
