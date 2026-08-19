<?php
/**
 * Privacy.
 *
 * Every disclosure that can be derived from the code is derived from it, so the
 * page cannot drift away from what the site actually does:
 *
 *   - the list of what the enquiry form collects comes from
 *     slap_enquiry_fields(), the same declaration that renders the form and
 *     validates it. Add a field and this page says so on the next request.
 *     It reads each field's 'discloses' name, not its form label: a label is a
 *     prompt ("Tell us about it"), a disclosure has to name a category.
 *   - the analytics and cookies sections only appear when SLAP_GA4_ID is set.
 *     While it is empty the site loads no third-party script and sets no
 *     cookie at all, and saying otherwise would be a false disclosure.
 *   - the "a copy is emailed" clause is gated on the SMTP environment variables
 *     that slap_enquiry_notify() actually tests, not on SLAP_ORG['email'],
 *     which is only the address printed in the footer.
 *
 * What CANNOT be derived is stated as plain prose and has to be checked by a
 * human when the deployment changes — above all the sentence naming the country
 * the server is in. It said South Africa in the first version; the server is in
 * Germany, which is a cross-border transfer POPIA requires be disclosed.
 *
 * Plain language on purpose: a privacy notice that nobody reads protects
 * nobody. It is written for a customer sending a photo of a rugby jersey.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/lib/bootstrap.php';
require_once dirname(__DIR__) . '/lib/enquiry.php';

slap_page('/privacy/');

$o      = SLAP_ORG;
$fields = slap_enquiry_fields();
$makers = implode(' and ', $o['makers']);

/**
 * Whether a copy of an enquiry actually leaves the server by email.
 *
 * Tests the same environment variables slap_enquiry_notify() tests, NOT
 * SLAP_ORG['email'] — that constant is the address printed in the footer and
 * has nothing to do with delivery. Gating the disclosure on it meant the page
 * could deny an email that was being sent, or claim one that never was.
 */
$emailsEnquiries = getenv('SLAP_SMTP_HOST') !== false && getenv('SLAP_SMTP_HOST') !== ''
    && getenv('SLAP_MAIL_TO') !== false && getenv('SLAP_MAIL_TO') !== ''
    && getenv('SLAP_MAIL_FROM') !== false && getenv('SLAP_MAIL_FROM') !== '';

$hasEmail = $o['email'] !== '';

/* strtotime() returns int|false, and under strict_types false into date() is a
   TypeError — a blank 500 with display_errors off. A malformed 'updated' in the
   manifest must not take the page down. */
$updatedTs = strtotime((string)(slap_current()['updated'] ?? ''));

require dirname(__DIR__) . '/partials/header.php';
?>

<section class="band page-head">
  <div class="wrap">
    <h1 class="page-title">Privacy</h1>
    <p class="lead">
      What happens to the details you send us, how long we keep them, and how to
      have them removed.
      <?= $updatedTs === false ? '' : 'Last updated ' . slap_e(date('j F Y', $updatedTs)) . '.' ?>
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
    <p>What you type into the enquiry form:</p>
    <ul>
      <?php foreach ($fields as $f): ?>
        <li>
          <strong><?= slap_e($f['discloses'] ?? $f['label']) ?></strong><?php
            if (empty($f['required'])) { echo ' — optional'; }
          ?>
        </li>
      <?php endforeach; ?>
    </ul>
    <p>
      We also record the date and time the enquiry arrived, so we can reply in
      order. We do not log your IP address and we do not profile you.
    </p>
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
      Your enquiry is saved on the server that runs this website. That server is
      in <strong>Germany</strong>, rented from Hetzner &mdash; so your details
      leave South Africa and are processed abroad. Germany is subject to the
      GDPR, which POPIA recognises as comparable protection.
      <?php if ($emailsEnquiries): ?>
        A copy is also emailed to us, which passes through our email provider.
      <?php endif; ?>
    </p>
    <p>
      Nobody at <?= slap_e($o['name']) ?> shares your enquiry with anyone else.
      The only other parties who could technically reach it are the companies
      that run the server<?php if ($emailsEnquiries): ?> and the email<?php endif; ?>
      on our behalf, and they may only act on our instructions.
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
        of the pages you view, along with your device and rough location. Google
        does not give us your full IP address and we cannot tell who you are from
        what we see. Google processes this on its own servers, outside South
        Africa.
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
