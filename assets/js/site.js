/**
 * The only JavaScript on the site. Loaded with defer from partials/footer.php.
 *
 * Two behaviours, and deliberately nothing else. No analytics, no carousel, no
 * scroll effects. Anything proposed for this file should first be tried as
 * markup or CSS — the collapsing nav is here only because doing it in CSS alone
 * needs a checkbox hack that lies to assistive technology about what the
 * control is.
 *
 * WHAT DEGRADES WITHOUT JAVASCRIPT
 *   The menu becomes a plain, always-visible list of links. Nothing is lost:
 *   the gallery filters are server-rendered links, the enquiry form is an
 *   ordinary POST, and every validation error is rendered by PHP into the page.
 *   The site is fully usable with scripting off.
 *
 * THE CSS CONTRACT (assets/css/nav.css owns every bit of showing and hiding;
 * this file sets no style, ever, so the menu's appearance lives in one place)
 *
 *   1. <html> gets data-js="on" as the first thing this file does. Both the
 *      narrow-breakpoint rule that hides #site-menu and the rule that reveals
 *      .masthead__toggle MUST be qualified by :root[data-js] — e.g.
 *
 *          :root[data-js] .masthead__toggle            { display: inline-flex }
 *          @media (max-width: 48rem) {
 *              :root[data-js] #site-menu[data-open="false"] { display: none }
 *          }
 *
 *      Without that qualifier a visitor with scripting off gets a menu hidden
 *      by CSS and a toggle button that cannot open it — the nav disappears, and
 *      it disappears silently, which is why it is spelled out here.
 *
 *   2. #site-menu carries data-open="true" | "false". PHP renders "false"; CSS
 *      keys off it at the narrow breakpoint and ignores it at the wide one.
 *
 *   3. .masthead__toggle carries aria-expanded and is a <button type="button">,
 *      so with scripting off it is inert rather than broken — and rule 1 keeps
 *      it out of sight in that case.
 *
 *   4. The two attributes are always written together. aria-expanded is what a
 *      screen reader announces, data-open is what the eye sees; letting them
 *      drift apart is exactly the bug this pairing exists to prevent.
 */
(function () {
    'use strict';

    // Declared before anything else so the stylesheet's :root[data-js] rules
    // apply on the first paint. `defer` means the DOM is already parsed and the
    // stylesheet is already applied, so this cannot flash an open menu shut.
    document.documentElement.setAttribute('data-js', 'on');

    var toggle = document.querySelector('.masthead__toggle');
    var menu   = document.getElementById('site-menu');

    if (toggle && menu) {
        toggle.addEventListener('click', function () {
            setOpen(toggle.getAttribute('aria-expanded') !== 'true');
        });

        // Escape closes and returns focus to the button. Without the focus
        // return a keyboard user who escapes the menu is stranded at the top of
        // the document with no visible caret and no idea where they are.
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
                setOpen(false);
                toggle.focus();
            }
        });

        // Activating a link inside the menu closes it. A cross-page link would
        // otherwise leave the panel open over the old page until navigation
        // completes, which reads as a stuck overlay on a slow connection.
        menu.addEventListener('click', function (event) {
            if (event.target && event.target.closest && event.target.closest('a')) {
                setOpen(false);
            }
        });
    }

    /**
     * A rejected submission re-renders the same URL with the errors at the top.
     * The browser restores the scroll position and leaves focus where it was,
     * so a screen-reader user hears nothing at all and the page looks unchanged
     * — indistinguishable from a form that did nothing. Moving focus to the
     * summary announces it and scrolls it into view.
     *
     * Both targets already carry tabindex="-1" in the PHP, so this only moves
     * focus; it never adds anything to the tab order.
     */
    var alarm = document.querySelector('.form__summary, .notice--bad');
    if (alarm) {
        alarm.focus();
    }

    function setOpen(open) {
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        menu.setAttribute('data-open', open ? 'true' : 'false');
    }
}());
