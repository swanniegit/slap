<?php
/**
 * Not found. Reached through Apache's ErrorDocument, so it must set the status
 * itself — served directly it is a real 404, and an empty page with a 200 is
 * how a broken URL ends up indexed.
 */
declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';

http_response_code(404);
slap_page('/404');
require __DIR__ . '/partials/header.php';
?>

<section class="band page-head">
  <div class="wrap">
    <h1 class="page-title">A dropped stitch</h1>
    <p class="lead">
      That page is not here. It may have moved, or the link may have been typed
      slightly wrong. Everything on this site is one of three places:
    </p>
    <p class="hero__actions">
      <a class="btn btn--ink" href="/gallery/">The gallery</a>
      <a class="btn btn--ghost" href="/enquiry/">Send an enquiry</a>
      <a class="btn btn--ghost" href="/">Start again at the top</a>
    </p>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
