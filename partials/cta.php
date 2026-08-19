<?php
/**
 * The closing call to action. One per page, always the last thing before the
 * footer, always asking for the same thing in the same words — the button says
 * "Send an enquiry" and the page it opens is headed "Tell us your plan".
 *
 * @var string $title
 * @var string $copy
 */
declare(strict_types=1);
?>
<section class="band band--coral cta seam-top">
  <div class="wrap cta__inner">
    <h2 class="cta__title"><?= slap_e($title) ?></h2>
    <p class="cta__copy"><?= slap_e($copy) ?></p>
    <a class="btn btn--paper" href="/enquiry/">Send an enquiry</a>
  </div>
</section>
