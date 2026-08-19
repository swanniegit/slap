<?php
/**
 * Gallery.
 *
 * The mockup's filter chips were decoration — four coloured pills that did
 * nothing. These are links that actually narrow the list, server-side, so they
 * work without JavaScript, survive a page refresh and can be sent to someone.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/lib/bootstrap.php';
require dirname(__DIR__) . '/lib/bears.php';

slap_page('/gallery/');

$collections = slap_collections();
$made        = $_GET['made'] ?? null;
$made        = is_string($made) && isset($collections[$made]) ? $made : null;
$bears       = slap_bears_in($made);
$total       = count(slap_bears());

// The breakdown in the lead is counted, not typed. Typed, it had already gone
// wrong: it claimed four bears came out of supporters' kit when lib/bears.php
// holds three, so the sentence contradicted the "Showing 3 of 7" line directly
// under it. Counting is also the only version that survives the next bear.
$madeUp = array_map(
    static fn(string $slug): int => count(slap_bears_in($slug)),
    array_combine(array_keys($collections), array_keys($collections))
);

require dirname(__DIR__) . '/partials/header.php';
?>

<section class="page-head band">
  <div class="wrap">
    <h1 class="page-title">Every bear so far</h1>
    <p class="lead">
      <?= $total ?> bears, finished and gone to the people they were made for.
      <?= $madeUp['kit'] ?> came out of supporters&rsquo; kit, <?= $madeUp['nursery'] ?> out of a
      cot sheet, <?= $madeUp['character'] ?> were made new and dressed. Yours will not look
      like any of them.
    </p>

    <nav class="chips" aria-label="Filter by what the bear was made from">
      <a class="chip<?= $made === null ? ' chip--on' : '' ?>" href="/gallery/"
         <?= $made === null ? 'aria-current="true"' : '' ?>>Everything</a>
      <?php foreach ($collections as $slug => $label): ?>
        <a class="chip<?= $made === $slug ? ' chip--on' : '' ?>"
           href="/gallery/?made=<?= slap_e($slug) ?>"
           <?= $made === $slug ? 'aria-current="true"' : '' ?>><?= slap_e($label) ?></a>
      <?php endforeach; ?>
    </nav>
  </div>
</section>

<section class="band gallery">
  <div class="wrap">
    <p class="gallery__count" role="status">
      <?php if ($made === null): ?>
        Showing all <?= $total ?> bears.
      <?php else: ?>
        Showing <?= count($bears) ?> of <?= $total ?> &mdash; <?= slap_e($collections[$made]) ?>.
      <?php endif; ?>
    </p>

    <?php /* heading: h2, because nothing on this page sits between the h1 and
             the cards — a card's default h3 would skip a level here.
             eagerUpTo: 1. Only the first card can be the LCP element, and
             slap_img() pairs eager with fetchpriority="high"; two high-priority
             images simply compete with each other for the same bandwidth. */ ?>
    <?php slap_partial('bear-grid', [
        'bears'     => $bears,
        'variant'   => 'full',
        'eagerUpTo' => 1,
        'heading'   => 'h2',
    ]); ?>
  </div>
</section>

<?php
slap_partial('cta', [
    'title' => 'Your bear next?',
    'copy'  => 'Send us a photo of the fabric and we will tell you what is possible with it.',
]);

require dirname(__DIR__) . '/partials/footer.php';
