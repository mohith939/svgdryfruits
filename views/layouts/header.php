<?php
/**
 * SVG Dry Fruits - Storefront Header Layout
 * Faithfully matches Next.js src/components/Header.tsx & layout.tsx
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Cart.php';
require_once ROOT_PATH . '/src/Icon.php';

$cartCount = Cart::count();
$activePage = $activePage ?? '';
$siteTitle = (!empty($pageTitle) ? $pageTitle . ' — ' : '') . 'SVG Dry Fruits';
?>
<!DOCTYPE html>
<html lang="en" data-scroll-behavior="smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($siteTitle) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@400;500;600;700&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Instrument+Serif:ital@0;1&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= Helpers::url('css/style.css') ?>">
  <!-- Cashfree JS v3 Drop-in SDK -->
  <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
  <script>
    window.SVG_APP = {
      baseUrl: '<?= Helpers::url('/') ?>',
      apiUrl: '<?= Helpers::url('api') ?>',
      cashfreeMode: '<?= strtolower(CASHFREE_ENV) ?>'
    };
  </script>
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>

  <header class="site-header">
    <div class="header-inner">
      <a class="wordmark" href="<?= Helpers::url('/') ?>" aria-label="SVG Dry Fruits home">
        <img src="<?= Helpers::asset('svg-dry-fruits-logo.jpg') ?>" alt="SVG Dry Fruits Logo" width="49" height="49">
      </a>

      <nav class="desktop-nav" aria-label="Main navigation">
        <a href="<?= Helpers::url('shop') ?>" <?= $activePage === 'shop' ? 'aria-current="page"' : '' ?>>Shop</a>
        <a href="<?= Helpers::url('combos') ?>" <?= $activePage === 'combos' ? 'aria-current="page"' : '' ?>>Combos &amp; Gifts</a>
        <a href="<?= Helpers::url('track') ?>" <?= $activePage === 'track' ? 'aria-current="page"' : '' ?>>Track Order</a>
        <a href="<?= Helpers::url('contact') ?>">Bulk Orders</a>
        <a href="<?= Helpers::url('contact') ?>" <?= $activePage === 'contact' ? 'aria-current="page"' : '' ?>>Contact</a>
      </nav>

      <div class="header-actions">
        <button class="icon-button search-toggle" type="button" aria-label="Show search" id="search-toggle-btn">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8" /><path d="m16 16 4.5 4.5" /></svg>
        </button>

        <a class="icon-button account-link" href="<?= Helpers::url('track') ?>" aria-label="Track Orders &amp; Account">
          <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
            <circle cx="12" cy="7" r="4" />
          </svg>
        </a>

        <a class="bag-link" href="<?= Helpers::url('cart') ?>" aria-label="Shopping bag, <?= $cartCount ?> items">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5.5 8.5h13l1 12h-15l1-12Z" /><path d="M9 9V6a3 3 0 0 1 6 0v3" /></svg>
          <span class="bag-count" id="header-bag-count"><?= $cartCount ?></span>
        </a>

        <button class="icon-button menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-nav" aria-label="Open navigation menu" id="menu-toggle-btn">
          <span></span><span></span>
        </button>
      </div>
    </div>

    <!-- Collapsible Search Panel -->
    <form id="search-panel" class="search-panel" role="search" action="<?= Helpers::url('shop') ?>" style="display:none;">
      <label for="site-search">Search the collection</label>
      <div class="search-row">
        <input id="site-search" name="q" type="search" placeholder="Try almonds or dates" />
        <button type="submit">Search</button>
      </div>
    </form>

    <!-- Collapsible Mobile Navigation -->
    <nav class="mobile-nav" id="mobile-nav" aria-label="Mobile navigation" style="display:none;">
      <a href="<?= Helpers::url('shop') ?>">Shop</a>
      <a href="<?= Helpers::url('combos') ?>">Combos &amp; Gifts</a>
      <a href="<?= Helpers::url('track') ?>">Track Order</a>
      <a href="<?= Helpers::url('contact') ?>">Bulk Orders</a>
      <a href="<?= Helpers::url('contact') ?>">Contact</a>
    </nav>
  </header>

  <main id="main">
