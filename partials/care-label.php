<?php
/**
 * The care label — the site's one structural device.
 *
 * It is a description list because that is what it is: a caption and the thing
 * it names. Rows come from lib/bears.php, so a bear says what is true of it
 * ("Kept: Vodacom badge") instead of being squeezed into a fixed schema.
 *
 * The foot line sits OUTSIDE the <dl>. A <dl> may contain only dt/dd groups or
 * the <div> wrappers that hold them, so a <p> among them is invalid — and the
 * foot is not a caption-and-value pair anyway, it is the label's hem.
 *
 * @var array $rows  list of [caption, value]
 * @var string $tone optional modifier: 'on-photo' when it sits over an image
 */
declare(strict_types=1);

$tone = $tone ?? '';
?>
<div class="care-label<?= $tone ? ' care-label--' . slap_e($tone) : '' ?>">
  <dl>
    <?php foreach ($rows as [$caption, $value]): ?>
      <div class="care-label__row">
        <dt class="care-label__caption"><?= slap_e($caption) ?></dt>
        <dd class="care-label__value"><?= slap_e($value) ?></dd>
      </div>
    <?php endforeach; ?>
  </dl>
  <p class="care-label__foot">One of one &middot; Handmade in <?= slap_e(SLAP_ORG['region']) ?></p>
</div>
