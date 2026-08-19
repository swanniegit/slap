<?php
/**
 * The hero.
 *
 * The thesis of the page is that a memory bear keeps the garment, so the hero
 * proves it rather than describing it: the Stormers bear still wearing its
 * sponsor badge, with the care label that says which jersey it was and what was
 * deliberately kept. The word "jersey" in the headline is set as an appliquéd
 * patch — the one place the seam device touches the type.
 *
 * @var array $lead   the bear that carries the claim
 * @var array $second the bear that widens it beyond sport
 */
declare(strict_types=1);
?>
<section class="hero">
  <div class="wrap hero__inner">

    <div class="hero__copy">
      <p class="eyebrow">Handmade in <?= slap_e(SLAP_ORG['region']) ?></p>

      <h1 class="hero__title">
        The <span class="patch">jersey</span><br>
        is still in there.
      </h1>

      <p class="lead">
        Nicolene and Irma Swanepoel sew keepsake bears by hand. Send the clothes that
        already mean something — a match jersey, a first cot sheet, a uniform — and
        they come back as a bear that still shows the badge, the print, the buttons.
      </p>

      <div class="hero__actions">
        <a class="btn btn--ink" href="/enquiry/">Send us your fabric</a>
        <a class="btn btn--ghost" href="/gallery/">See what we have made</a>
      </div>
    </div>

    <div class="hero__proof">
      <figure class="hero__panel panel-stitched">
        <?= slap_img($lead['image'], $lead['alt'], ['eager' => true, 'class' => 'hero__photo']) ?>
        <figcaption>
          <?php slap_partial('care-label', ['rows' => $lead['label'], 'tone' => 'on-photo']); ?>
        </figcaption>
      </figure>

      <figure class="hero__panel hero__panel--minor panel-stitched">
        <?= slap_img($second['image'], $second['alt'], ['class' => 'hero__photo']) ?>
        <figcaption class="hero__minor-caption"><?= slap_e($second['label'][0][1]) ?></figcaption>
      </figure>
    </div>

  </div>
</section>
