<?php
/**
 * Privacy.
 *
 * Two things here are generated rather than written out, so the page cannot
 * drift away from what the site actually does:
 *
 *   - the list of what the enquiry form collects comes from
 *     slap_enquiry_fields(), the same declaration that renders the form and
 *     validates it. Add a field and this page says so on the next request.
 *   - the analytics and cookies sections only appear when SLAP_GA4_ID is set.
 *     While it is empty the site loads no third-party script and sets no
 *     cookie at all, and saying otherwise would be a false disclosure.
 *
 * Plain language on purpose: a privacy notice that nobody reads protects
 * nobody. It is written for a customer sending a photo of a rugby jersey.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/lib/bootstrap.php';
require dirname(__DIR__) . '/lib/enquiry.php';

slap_page('/privacy/');

$o        = SLAP_ORG;
$fields   = slap_enquiry_fields();
$makers   = implode(' and ', $o['makers']);
$hasEmail = $o['email'] !== '';

require dirname(__DIR__) . '/partials/header.php';
?>

<section class="band page-head">
  <div class="wrap">
    <h1 class="page-title">Privacy</h1>
    <p class="lead">
      What happens to the details you send us, how long we keep them, and how to
      have them removed. Last updated <?= slap_e(date('j F Y', strtotime(slap_current()['updated']))) ?>.
    </p>
  </div>
</section>

<section class="band prose">
  <div class="wrap">

    <h2>Who we are</h2>
    <p>
      <?= slap_e($o['name']) ?> is <?= slap_e($makers) ?>, sewing keepsake bears
      in <?= slap_e($o['region']) ?>. We are the responsible party for the
      personal information described here, under the Protection of Personal
      Information Act (POPIA).
    </p>

    <h2>What we collect</h2>
    <p>Only what you type into the enquiry form:</p>
    <ul>
      <?php foreach ($fields as $f): ?>
        <li>
          <strong><?= slap_e($f['label']) ?></strong><?php
            if (empty($f['required'])) { echo ' — optional'; }
          ?>
        </li>
      <?php endforeach; ?>
    </ul>
    <p>
      We do not ask for an address, an ID number or any payment details on this
      site, and there is nowhere to enter them. If you go ahead with an order,
      we arrange postage and payment with you directly.
    </p>

    <h2>What we do with it</h2>
    <p>
      We read it and reply to you about your bear. That is the only reason we
      have it. We do not sell it, and we do not send marketing to anyone who has
      not asked for it.
    </p>
    <p>
      Your enquiry is saved on the server that runs this website, in South
      Africa<?php if ($hasEmail): ?>, and a copy is emailed to us so we see it<?php endif; ?>.
      Nobody outside <?= slap_e($o['name']) ?> is given access to it.
    </p>

    <h2>How long we keep it</h2>
    <p>
      For as long as we are talking to you about a bear, and for two years
      afterwards in case you come back to us about the same one. Ask us to
      delete yours sooner and we will, without needing a reason.
    </p>

    <h2>Photographs of your bear</h2>
    <p>
      We sometimes show finished bears in the gallery on this site or on social
      media. We ask first, and the fabric you sent is never identified as
      belonging to a particular person unless you have said we may. If a bear of
      yours is already up and you would rather it were not, tell us and it comes
      down.
    </p>

    <h2>Cookies</h2>
    <?php if (SLAP_GA4_ID === ''): ?>
      <p>
        This site sets no cookies. There is no tracking, no advertising network
        and no third-party script following you between pages.
      </p>
    <?php else: ?>
      <p>
        We use Google Analytics to count visits and see which pages people
        actually read. It sets cookies in your browser and sends Google a record
        of the pages you view. We have IP anonymisation switched on, so Google is
        not given your full IP address, and we cannot see who you are from it.
      </p>
      <p>
        Blocking cookies in your browser, or using a browser extension that
        blocks analytics, stops this entirely and nothing on the site breaks.
      </p>
    <?php endif; ?>

    <h2>Your rights</h2>
    <p>Under POPIA you may ask us to:</p>
    <ul>
      <li>tell you what personal information of yours we hold;</li>
      <li>correct anything that is wrong;</li>
      <li>delete it;</li>
      <li>stop using it for a particular purpose.</li>
    </ul>
    <p>
      <?php if ($hasEmail): ?>
        Email <a href="mailto:<?= slap_e($o['email']) ?>"><?= slap_e($o['email']) ?></a>
        and we will do it. There is no charge and you do not need a reason.
      <?php else: ?>
        Contact us through the enquiry form and we will do it. There is no charge
        and you do not need a reason.
      <?php endif; ?>
    </p>
    <p>
      If you are not happy with how we have handled it, you can complain to the
      Information Regulator of South Africa at
      <a href="https://inforegulator.org.za" rel="noopener">inforegulator.org.za</a>.
    </p>

    <h2>Changes</h2>
    <p>
      If this changes, the date at the top of the page changes with it. We do not
      quietly rewrite it.
    </p>

  </div>
</section>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
