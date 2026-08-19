<?php
/**
 * The four facts band.
 *
 * A definition list, not four <div>s of loose spans: each item is a claim and
 * the sentence that qualifies it, which is exactly what <dt>/<dd> mean. Colours
 * come from a modifier class per item, so the CSS owns the palette and this
 * file owns the words.
 */
declare(strict_types=1);

$facts = [
    ['gold',  'One of one',   'No two bears leave here the same, because no two garments arrive the same.'],
    ['sky',   'Your fabric',  'Match jerseys, cot sheets, baby-grows, uniforms, a shirt nobody can throw away.'],
    ['coral', 'Two sisters',  'Nicolene and Irma, cutting and sewing every panel between them.'],
    ['grape', 'Made to order','Nothing is kept in stock. Yours starts once you have told us about it.'],
];
?>
<section class="band band--ink facts">
  <div class="wrap">
    <dl class="facts__grid">
      <?php foreach ($facts as [$tone, $claim, $detail]): ?>
        <div class="facts__item facts__item--<?= slap_e($tone) ?>">
          <dt class="facts__claim"><?= slap_e($claim) ?></dt>
          <dd class="facts__detail"><?= slap_e($detail) ?></dd>
        </div>
      <?php endforeach; ?>
    </dl>
  </div>
</section>
