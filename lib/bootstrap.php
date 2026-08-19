<?php
/**
 * The single entry point. Every page requires this first, then calls
 * slap_page('/its/manifest/key') and includes partials/header.php.
 */
declare(strict_types=1);

define('SLAP_ROOT', dirname(__DIR__));

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/html.php';
require_once __DIR__ . '/seo.php';
require_once __DIR__ . '/nav.php';

/**
 * Security headers that need a per-request value and so cannot live in
 * .htaccess. The static ones (nosniff, Referrer-Policy, HSTS) are set there.
 *
 * The CSP is ENFORCED. It shipped Report-Only, waiting for "the reports to be
 * quiet" — but no report-uri or report-to was ever set, so no report was sent
 * anywhere and the condition could never be met. A Report-Only policy with
 * nowhere to report is a header that does nothing at all, which is worse than
 * an absent one: it reads, to whoever comes next, like a safeguard.
 *
 * It was promoted on evidence rather than hope. With the fonts self-hosted the
 * policy has no third-party origin left in it, there is not one inline style
 * attribute or <style> block in the markup, and the only inline script is the
 * JSON-LD, which carries slap_nonce(). Loading all four pages under the policy
 * in a real browser raised zero securitypolicyviolation events.
 *
 * style-src and font-src are 'self' with nothing beside them: Baloo 2 and Karla
 * are served from assets/fonts/ now, so no stylesheet, font or script on any
 * page comes from a host this site does not control. That was the outstanding
 * item this comment used to describe.
 */
function slap_send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    $csp = implode('; ', [
        "default-src 'self'",
        "script-src 'self' 'nonce-" . slap_nonce() . "' https://www.googletagmanager.com",
        "style-src 'self'",
        "font-src 'self'",
        "img-src 'self' data: https://www.googletagmanager.com https://*.google-analytics.com",
        "connect-src 'self' https://*.google-analytics.com https://*.analytics.google.com https://www.googletagmanager.com",
        "base-uri 'self'",
        "form-action 'self'",
        "object-src 'none'",
        "frame-ancestors 'none'",
        'upgrade-insecure-requests',
    ]);

    // scripts/smoke.sh asserts this header by name. Reverting it to Report-Only
    // reds the suite, because that revert is invisible in a browser.
    header('Content-Security-Policy: ' . $csp);
    header('Cache-Control: public, max-age=0, must-revalidate');
}
