<?php
/**
 * The two ways to get a bear — two panels joined down a centre seam, because
 * the choice really is a fork and not a sequence. No 01 / 02 numbering: nobody
 * does both, and numbering a pair of alternatives implies an order that does
 * not exist.
 */
declare(strict_types=1);

$routes = [
    [
        'tone'  => 'gold',
        'kind'  => 'Memory bears',
        'title' => 'Send us the clothes',
        'copy'  => 'A match jersey, a first cot sheet, a uniform, a shirt nobody can bring
                    themselves to give away. We unpick it, place the panels so the badges and
                    prints land where you would want them, and sew it back up as a bear.',
        'href'  => '/enquiry/',
        'cta'   => 'Start an enquiry',
    ],
    [
        'tone'  => 'sky',
        'kind'  => 'Character bears',
        'title' => 'Pick a theme',
        'copy'  => 'Made new in fresh cotton, corduroy or leatherette, then dressed: a nurse,
                    a theatre bear, a girl in a yellow pinafore. A soft, safe gift for a baby
                    shower, a christening, a graduation or a last day on the ward.',
        'href'  => '/gallery/?made=character',
        'cta'   => 'See the character bears',
    ],
];
?>
<section class="band routes">
  <div class="wrap">
    <h2 class="section-title">Two ways to get a bear</h2>

    <div class="routes__pair">
      <?php foreach ($routes as $r): ?>
        <article class="routes__panel routes__panel--<?= slap_e($r['tone']) ?> panel-stitched">
          <p class="chip chip--<?= slap_e($r['tone']) ?>"><?= slap_e($r['kind']) ?></p>
          <h3 class="routes__title"><?= slap_e($r['title']) ?></h3>
          <p class="routes__copy"><?= slap_e(preg_replace('/\s+/', ' ', $r['copy'])) ?></p>
          <a class="link-arrow" href="<?= slap_e($r['href']) ?>"><?= slap_e($r['cta']) ?></a>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
