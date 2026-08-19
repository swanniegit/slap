<?php
/**
 * What happens after you press send.
 *
 * Numbered, and here the numbers earn it: this is a real sequence with a real
 * order, and the reader needs to know that the fabric is posted at step two and
 * not before. (The gallery and the two routes are not sequences, so neither of
 * them is numbered.)
 */
declare(strict_types=1);

$steps = [
    ['You send the details.',  'Photos of the fabric, or of the bear you have in mind. Rough is fine.'],
    ['We reply with a plan.',  'What is possible with that fabric, what it costs, how long it takes and where to post it.'],
    ['We sew it by hand.',     'You get progress photos from the machine before it is packed and sent.'],
];
?>
<ol class="steps">
  <?php foreach ($steps as $i => [$title, $detail]): ?>
    <li class="steps__step">
      <span class="steps__number" aria-hidden="true"><?= $i + 1 ?></span>
      <span class="steps__body">
        <strong class="steps__title"><?= slap_e($title) ?></strong>
        <span class="steps__detail"><?= slap_e($detail) ?></span>
      </span>
    </li>
  <?php endforeach; ?>
</ol>
