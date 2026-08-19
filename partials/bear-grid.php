<?php
/**
 * A grid of bear cards.
 *
 * @var array  $bears   entries from slap_bears()
 * @var string $variant passed through to partials/bear-card.php
 * @var int    $eagerUpTo how many leading cards load eagerly (above the fold)
 * @var string $heading   heading level for each card's name — see bear-card.php
 */
declare(strict_types=1);

$variant   = $variant ?? 'full';
$eagerUpTo = $eagerUpTo ?? 0;
$heading   = $heading ?? 'h3';
?>
<div class="bear-grid bear-grid--<?= slap_e($variant) ?>">
  <?php foreach ($bears as $i => $bear): ?>
    <?php slap_partial('bear-card', [
        'bear'    => $bear,
        'variant' => $variant,
        'eager'   => $i < $eagerUpTo,
        'heading' => $heading,
    ]); ?>
  <?php endforeach; ?>
</div>
