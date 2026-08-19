<?php
/**
 * Closes the document.
 *
 * Contact links render only when lib/config.php actually has the value. The
 * mockup footers linked "Facebook / Instagram / WhatsApp" at href="#" on all
 * three pages — three dead links that look live, on the one block of a craft
 * site people actually click.
 */
declare(strict_types=1);

$o = SLAP_ORG;

/** [label, href] pairs for every contact channel that has been filled in. */
$contact = array_values(array_filter([
    $o['whatsapp']  ? ['WhatsApp', $o['whatsapp']] : null,
    $o['facebook']  ? ['Facebook', $o['facebook']] : null,
    $o['instagram'] ? ['Instagram', $o['instagram']] : null,
    $o['email']     ? [$o['email'], 'mailto:' . $o['email']] : null,
    $o['phone']     ? [$o['phone_display'] ?: $o['phone'], 'tel:' . $o['phone']] : null,
]));
?>
</main>

<footer class="footer band band--ink seam-top">
  <div class="wrap footer__grid">

    <div class="footer__about">
      <p class="footer__wordmark"><?= slap_e($o['name']) ?></p>
      <p class="footer__blurb">
        <?= slap_e($o['tagline']) ?>. Keepsake bears and creative baby gifts,
        sewn by hand by <?= slap_e(implode(' and ', $o['makers'])) ?>
        in <?= slap_e($o['region']) ?>.
      </p>
    </div>

    <nav class="footer__col" aria-label="Pages">
      <h2 class="label">Pages</h2>
      <ul class="footer__links">
        <?php /* Home and the privacy page are not masthead items — the brand
                 mark links home, and privacy belongs at the bottom. Both come
                 from the manifest via slap_footer_links(), so adding a page is
                 still one manifest edit and nothing is typed twice. */ ?>
        <?php foreach (slap_footer_links() as $link): ?>
          <li><a href="<?= slap_e($link['path']) ?>"><?= slap_e($link['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <?php if ($contact): ?>
      <div class="footer__col">
        <h2 class="label">Talk to us</h2>
        <ul class="footer__links">
          <?php foreach ($contact as [$text, $href]): ?>
            <li><a href="<?= slap_e($href) ?>" rel="noopener"><?= slap_e($text) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

  </div>

  <p class="wrap footer__legal label">
    &copy; <?= date('Y') ?> <?= slap_e($o['name']) ?> &middot; Handmade in <?= slap_e($o['region']) ?>
  </p>
</footer>

<script src="/assets/js/site.js" defer></script>
</body>
</html>
