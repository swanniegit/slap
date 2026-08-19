<?php
/**
 * Enquiry — one URL that both renders the form and handles its POST.
 *
 * One URL rather than a separate handler: the form has to come back with the
 * typed-in values and the error messages when something is missing, and a
 * redirect would throw both away. A successful submission redirects (303) so
 * that a refresh cannot send the same enquiry twice.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/lib/bootstrap.php';
require dirname(__DIR__) . '/lib/enquiry.php';

slap_page('/enquiry/');

$values = [];
$errors = [];
$failed = false;
$sent   = isset($_GET['sent']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $result = slap_enquiry_validate($_POST);

    if ($result['drop'] !== null) {
        // Answer a bot exactly as we answer a person: no hint about which check
        // caught it. Logged so a sudden burst is visible in the container logs.
        error_log('slap: enquiry dropped (' . $result['drop'] . ')');
        header('Location: /enquiry/?sent=1', true, 303);
        exit;
    }

    if ($result['errors'] !== []) {
        http_response_code(422);
        $values = $result['values'];
        $errors = $result['errors'];
    } elseif (slap_enquiry_store($result['values'])) {
        slap_enquiry_notify($result['values']);
        header('Location: /enquiry/?sent=1', true, 303);
        exit;
    } else {
        // The write failed, so there is no record. Say so rather than showing
        // the thank-you page over an enquiry that does not exist anywhere.
        http_response_code(500);
        $values = $result['values'];
        $failed = true;
    }
}

require dirname(__DIR__) . '/partials/header.php';
?>

<section class="band enquiry">
  <div class="wrap enquiry__grid">

    <div class="enquiry__intro">
      <h1 class="page-title">Tell us your plan</h1>
      <p class="lead">
        Send us what you have and what you would like made. We come back with
        what is possible with that fabric, how long it takes and what it costs.
        No detail is too small.
      </p>
      <?php slap_partial('enquiry-steps'); ?>
    </div>

    <div class="enquiry__form">
      <?php if ($sent): ?>
        <div class="notice notice--good panel-stitched">
          <h2 class="notice__title">Sounds like a plan</h2>
          <p>Your enquiry is in. Nicolene or Irma will read it and come back to you.</p>
          <p>If you have photos of the fabric, have them ready — they are the first thing we will ask for.</p>
          <p class="notice__more"><a class="link-arrow" href="/gallery/">Look at the bears while you wait</a></p>
        </div>

      <?php else: ?>
        <?php if ($failed): ?>
          <div class="notice notice--bad panel-stitched" role="alert" tabindex="-1">
            <h2 class="notice__title">That did not save</h2>
            <p>
              Something went wrong on our side and your enquiry was not stored, so
              nobody has seen it. Nothing you typed has been lost — send it again,
              <?php if (SLAP_ORG['email'] !== ''): ?>
                or email <a href="mailto:<?= slap_e(SLAP_ORG['email']) ?>"><?= slap_e(SLAP_ORG['email']) ?></a>.
              <?php else: ?>
                and if it happens twice, please try again later.
              <?php endif; ?>
            </p>
          </div>
        <?php endif; ?>

        <?php slap_partial('enquiry-form', ['values' => $values, 'errors' => $errors]); ?>
      <?php endif; ?>
    </div>

  </div>
</section>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
