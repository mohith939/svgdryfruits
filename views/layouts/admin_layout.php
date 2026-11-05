<?php
/**
 * SVG Dry Fruits - Admin Topbar & Layout Header
 * Exact 1:1 match of Node.js AdminTopNav.tsx and AdminLayout
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Auth.php';

Auth::requireAuth();
$admin = Auth::admin();
$adminEmail = $admin['email'] ?? 'svgdryfruits@gmail.com';
$initial = strtoupper(substr($adminEmail, 0, 1)) ?: 'M';
$activeNav = $activeNav ?? 'dashboard';

$navItems = [
    [
        'id' => 'dashboard',
        'label' => 'Overview',
        'href' => Helpers::url('admin'),
        'exact' => true,
        'svg' => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" /><polyline points="9 22 9 12 15 12 15 22" />',
    ],
    [
        'id' => 'products',
        'label' => 'Products & Stock',
        'href' => Helpers::url('admin/products'),
        'exact' => false,
        'svg' => '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" /><path d="m3.3 7 8.7 5 8.7-5" /><path d="M12 22V12" />',
    ],
    [
        'id' => 'categories',
        'label' => 'Categories',
        'href' => Helpers::url('admin/categories'),
        'exact' => false,
        'svg' => '<polygon points="12 2 2 7 12 12 22 7 12 2" /><polyline points="2 17 12 22 22 17" /><polyline points="2 12 12 17 22 12" />',
    ],
    [
        'id' => 'combos',
        'label' => 'Combos',
        'href' => Helpers::url('admin/combos'),
        'exact' => false,
        'svg' => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" /><line x1="7" y1="7" x2="7.01" y2="7" />',
    ],
    [
        'id' => 'orders',
        'label' => 'Orders',
        'href' => Helpers::url('admin/orders'),
        'exact' => false,
        'svg' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /><line x1="16" y1="13" x2="8" y2="13" /><line x1="16" y1="17" x2="8" y2="17" /><polyline points="10 9 9 9 8 9" />',
    ],
    [
        'id' => 'coupons',
        'label' => 'Coupons',
        'href' => Helpers::url('admin/coupons'),
        'exact' => false,
        'svg' => '<line x1="19" y1="5" x2="5" y2="19" /><circle cx="6.5" cy="6.5" r="2.5" /><circle cx="17.5" cy="17.5" r="2.5" />',
    ],
    [
        'id' => 'shipping',
        'label' => 'Shipping',
        'href' => Helpers::url('admin/shipping'),
        'exact' => false,
        'svg' => '<rect x="1" y="3" width="15" height="13" /><polygon points="16 8 20 8 23 11 23 16 16 16 16 8" /><circle cx="5.5" cy="18.5" r="2.5" /><circle cx="18.5" cy="18.5" r="2.5" />',
    ],
    [
        'id' => 'settings',
        'label' => 'Settings',
        'href' => Helpers::url('admin/settings'),
        'exact' => false,
        'svg' => '<circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> | SVG Dry Fruits Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Instrument+Serif:ital@0;1&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= Helpers::url('css/style.css') ?>">
</head>
<body>
  <div class="admin-shell">
    <header class="admin-v4-topbar">
      <div class="admin-v4-topbar-left">
        <!-- Brand Logo -->
        <a href="<?= Helpers::url('admin') ?>" class="admin-v4-brand" aria-label="SVG Admin Dashboard">
          <img
            src="<?= Helpers::asset('login-emblem-badge.png') ?>"
            alt=""
            class="admin-v4-brand-emblem"
          >
          <div class="admin-v4-brand-text">
            <span class="admin-v4-brand-title">SVG</span>
            <span class="admin-v4-brand-sub">DRY FRUITS</span>
          </div>
        </a>

        <!-- Navigation Items with Exact Line Icons -->
        <nav class="admin-v4-nav" aria-label="Admin Navigation">
          <?php foreach ($navItems as $item): 
            $isActive = ($activeNav === $item['id']);
          ?>
            <a
              href="<?= $item['href'] ?>"
              class="admin-v4-nav-item <?= $isActive ? 'admin-v4-nav-active' : '' ?>"
            >
              <span class="admin-v4-nav-icon">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <?= $item['svg'] ?>
                </svg>
              </span>
              <span><?= htmlspecialchars($item['label']) ?></span>
            </a>
          <?php endforeach; ?>
        </nav>
      </div>

      <div class="admin-v4-topbar-right">
        <!-- View Store -->
        <a href="<?= Helpers::url('/') ?>" target="_blank" class="admin-v4-view-store">
          <span>View Store</span>
          <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="7" y1="17" x2="17" y2="7" />
            <polyline points="7 7 17 7 17 17" />
          </svg>
        </a>

        <div class="admin-v4-divider"></div>

        <!-- Email -->
        <span class="admin-v4-email"><?= htmlspecialchars($adminEmail) ?></span>

        <!-- User Profile Dropdown -->
        <div class="admin-v4-profile-wrap">
          <button
            type="button"
            id="adminProfileBtn"
            class="admin-v4-profile-btn"
            aria-label="User Profile Menu"
          >
            <div class="admin-v4-avatar"><?= $initial ?></div>
            <div class="admin-v4-user-info">
              <span class="admin-v4-user-name">Mohith</span>
              <span class="admin-v4-user-role">
                Admin
                <svg viewBox="0 0 24 24" width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-left: 3px;">
                  <polyline points="6 9 12 15 18 9" />
                </svg>
              </span>
            </div>
          </button>

          <div id="adminProfileDropdown" class="admin-v4-dropdown" style="display: none;">
            <div class="admin-v4-dropdown-header">
              <span class="admin-v4-dropdown-name">Mohith Admin</span>
              <span class="admin-v4-dropdown-email"><?= htmlspecialchars($adminEmail) ?></span>
            </div>
            <div class="admin-v4-dropdown-divider"></div>
            <a href="<?= Helpers::url('admin/settings') ?>" class="admin-v4-dropdown-item">
              Store Settings
            </a>
            <a href="<?= Helpers::url('admin/logout') ?>" class="admin-v4-dropdown-logout" style="display:block; text-decoration:none;">
              Sign out
            </a>
          </div>
        </div>
      </div>
    </header>

    <main class="admin-main">
      <?php foreach (Helpers::getFlashes() as $f): ?>
        <div style="padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 13.5px; font-weight: 500; <?= $f['type'] === 'error' ? 'background: #FDF0ED; color: #C24B38; border: 1px solid #F5C6CB;' : 'background: #EAF5EC; color: #2E7D32; border: 1px solid #C3E6CB;' ?>">
          <?= htmlspecialchars($f['message']) ?>
        </div>
      <?php endforeach; ?>
