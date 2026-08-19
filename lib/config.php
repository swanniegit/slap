<?php
/**
 * Business facts and site-wide constants. The single copy.
 *
 * Everything here was repeated across three hand-built mockup pages — the name,
 * the sisters, the footer blurb, the nav. One edit here now reaches every page,
 * every JSON-LD block, the sitemap and the enquiry email.
 *
 * TODO markers are facts SLAP still has to supply. They are constants rather
 * than inline placeholders so that filling them in is one edit, and so that
 * scripts/check-manifest.php can fail CI while any of them are still empty in a
 * production build.
 */
declare(strict_types=1);

/**
 * The site's own address, feeding every canonical, og:url, JSON-LD @id and
 * sitemap entry.
 *
 * Read from the environment because the same image is served on more than one
 * hostname: slap.yellowarcher.co.za while the domain is provisioning, and
 * slapbabydesigns.co.za once it resolves. Coolify sets SLAP_BASE_URL per
 * application, so moving between them is an env change and a redeploy rather
 * than a commit — which is how the other sites on that server already work.
 *
 * define() rather than const: a const initialiser cannot call a function.
 * The trailing slash is stripped so slap_abs('/') cannot produce a double one.
 */
define('SLAP_BASE_URL', rtrim(getenv('SLAP_BASE_URL') ?: 'https://slapbabydesigns.co.za', '/'));

const SLAP_ORG = [
    'name'      => 'SLAP Baby Designs',
    'tagline'   => 'Sounds Like a Plan',
    'makers'    => ['Nicolene Swanepoel', 'Irma Swanepoel'],
    'country'   => 'ZA',
    'region'    => 'South Africa',
    'languages' => ['en', 'af'],

    // TODO(slap): supply. Empty values are rendered as nothing, never as a
    // fake link — a dead "WhatsApp" in the footer costs more than an absent one.
    'email'         => '',
    'phone'         => '',           // E.164, e.g. +27821234567
    'phone_display' => '',           // e.g. 082 123 4567
    'whatsapp'      => '',           // https://wa.me/27821234567
    'facebook'      => '',
    'instagram'     => '',
];

/**
 * Where enquiries go.
 *
 * Every enquiry is appended to the JSONL file first and emailed second, in that
 * order. Storage is the record; email is the notification. If SMTP is down or
 * unconfigured the enquiry is still on disk and nothing is lost — the failure
 * mode of the mockup form (silently discard) is the one thing this must not do.
 */
const SLAP_ENQUIRY_LOG = '/data/enquiries.jsonl';

/** Minimum seconds between form render and submit. Below this it is a script. */
const SLAP_MIN_FILL_SECONDS = 4;

/**
 * Search Console ownership token, rendered as <meta name="google-site-verification">
 * by partials/head.php when it is not empty.
 *
 * There is deliberately no analytics ID beside it: assets/js/site.js carries no
 * analytics and nothing on the site reads one, so a constant for it would be a
 * setting that looks configurable and changes nothing.
 */
const SLAP_GSC_TOKEN = '';   // TODO(slap): paste the Search Console token
