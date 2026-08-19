<?php
/**
 * One bear. The only markup that renders a bear anywhere on the site — the
 * mockup repeated this block seven times in the gallery and four more on the
 * home page, which is why four of them still said "Caption to come".
 *
 * @var array  $bear    an entry from slap_bears()
 * @var string $variant 'full' (gallery: blurb and care label) or
 *                      'brief' (home strip: name only)
 * @var bool   $eager   skip lazy-loading, for cards above the fold
 * @var string $heading 'h2' or 'h3' — the level this card's name sits at
 *
 * The heading level is passed in and not fixed, because the correct level
 * depends on what is above the card. On the home page the strip has its own
 * "Fresh off the machine" h2, so a bear is an h3 under it. The gallery has no
 * such heading — the page title IS the heading for the whole list — so a
 * hardcoded h3 there skips a level straight from the h1, which is a real
 * failure of WCAG 1.3.1 and leaves a screen-reader user's heading list with a
 * hole in it.
 */
declare(strict_types=1);

$variant = $variant ?? 'full';
$eager   = $eager ?? false;
$heading = ($heading ?? 'h3') === 'h2' ? 'h2' : 'h3';
?>
<figure class="bear bear--<?= slap_e($variant) ?> panel-stitched" id="bear-<?= slap_e($bear['slug']) ?>">
  <?= slap_img($bear['image'], $bear['alt'], ['class' => 'bear__photo', 'eager' => $eager]) ?>

  <figcaption class="bear__body">
    <<?= $heading ?> class="bear__name"><?= slap_e($bear['name']) ?></<?= $heading ?>>

    <?php if ($variant === 'full'): ?>
      <p class="bear__blurb"><?= slap_e($bear['blurb']) ?></p>
      <?php slap_partial('care-label', ['rows' => $bear['label']]); ?>
    <?php else: ?>
      <p class="bear__source"><?= slap_e($bear['label'][0][1]) ?></p>
    <?php endif; ?>
  </figcaption>
</figure>
