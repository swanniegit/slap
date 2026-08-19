<?php
/**
 * Opens the document and renders the masthead. Every page includes this once.
 *
 * The nav is built from slap_nav_items(), so the three drifted copies in the
 * mockups collapse to this one, and the current page is marked with
 * aria-current rather than only a coloured underline.
 */
declare(strict_types=1);

require SLAP_ROOT . '/partials/head.php';
?>
<body>
<a class="skip" href="#main">Skip to content</a>

<header class="masthead seam-bottom">
  <div class="masthead__inner wrap">
    <a class="brand" href="/">
      <span class="brand__patch" aria-hidden="true">S</span>
      <span class="brand__names">
        <span class="brand__name"><?= slap_e(SLAP_ORG['name']) ?></span>
        <span class="brand__tagline"><?= slap_e(SLAP_ORG['tagline']) ?></span>
      </span>
    </a>

    <button class="masthead__toggle" type="button"
            aria-expanded="false" aria-controls="site-menu">
      <span class="masthead__toggle-bar" aria-hidden="true"></span>
      <span class="masthead__toggle-text">Menu</span>
    </button>

    <nav aria-label="Primary">
      <ul class="menu" id="site-menu" data-open="false">
        <?php foreach (slap_nav_items() as $item): ?>
          <li class="menu__item">
            <a class="<?= $item['cta'] ? 'btn btn--coral' : 'menu__link' ?>"
               href="<?= slap_e($item['path']) ?>"
               <?= slap_is_current($item['path']) ? 'aria-current="page"' : '' ?>><?= slap_e($item['label']) ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>
  </div>
</header>

<?php /* tabindex="-1" so that following the skip link actually moves FOCUS to
         the content and not only the scroll position. Without it several
         browsers scroll to #main and leave the caret in the skip link, so the
         next Tab goes back into the masthead — the skip link appears to do
         nothing, which is the one thing it must not do. -1 keeps it out of the
         tab order; it can only be reached by being sent there. */ ?>
<main id="main" tabindex="-1">
