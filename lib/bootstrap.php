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
 * The CSP ships Report-Only. To enforce it, change the header name on the one
 * line below that writes it — there is no $enforce flag, because a parameter
 * that every caller leaves at its default is a setting that only looks like
 * one. Do it once the reports are quiet: a strict policy that silently kills
 * the JSON-LD block or the nav toggle is worse than no policy, because nothing
 * visibly breaks in the author's browser.
 *
 * fonts.googleapis.com / fonts.gstatic.com are allowed because Baloo 2 and
 * Karla are loaded from Google Fonts. Self-hosting them would let this drop to
 * 'self' and is the right next step; it is a deliberate outstanding item.
 */
function slap_send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    $csp = implode('; ', [
        "default-src 'self'",
        "script-src 'self' 'nonce-" . slap_nonce() . "'",
        "style-src 'self' https://fonts.googleapis.com",
        "font-src 'self' https://fonts.gstatic.com",
        "img-src 'self' data:",
        "connect-src 'self'",
        "base-uri 'self'",
        "form-action 'self'",
        "object-src 'none'",
        "frame-ancestors 'none'",
        'upgrade-insecure-requests',
    ]);

    // To enforce: 'Content-Security-Policy-Report-Only' -> 'Content-Security-Policy'.
    header('Content-Security-Policy-Report-Only: ' . $csp);
    header('Cache-Control: public, max-age=0, must-revalidate');
}
