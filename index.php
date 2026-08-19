<?php
/**
 * Home. Assembles partials and passes them data; it holds no markup of its own
 * beyond the strip heading, so a section can be reordered by moving one line.
 */
declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/bears.php';

slap_page('/');
require __DIR__ . '/partials/header.php';

slap_partial('hero', [
    'lead'   => slap_bear('stormers'),
    'second' => slap_bear('pooh-print'),
]);

slap_partial('facts');
slap_partial('routes');
?>

<section class="band recent">
  <div class="wrap">
    <div class="section-head">
      <h2 class="section-title">Fresh off the machine</h2>
      <a class="link-arrow" href="/gallery/">All <?= count(slap_bears()) ?> bears</a>
    </div>

    <?php slap_partial('bear-grid', [
        'bears'   => slap_bears_recent(4),
        'variant' => 'brief',
    ]); ?>
  </div>
</section>

<?php
slap_partial('cta', [
    'title' => 'Got fabric with a memory in it?',
    'copy'  => 'Send a photo of what you have and tell us who it is for. We come back with what is possible, how long it takes and what it costs.',
]);

require __DIR__ . '/partials/footer.php';
