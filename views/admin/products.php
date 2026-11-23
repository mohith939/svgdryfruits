<?php
/**
 * SVG Dry Fruits - Products & Inventory Management
 * 1:1 match of Node.js src/app/admin/products/page.tsx
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Models.php';
require_once ROOT_PATH . '/src/Auth.php';

Auth::requireAuth();

$pdo = Database::getConnection();

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Helpers::requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_active') {
        $prodId = $_POST['product_id'] ?? '';
        $stmt = $pdo->prepare("UPDATE products SET active = 1 - active WHERE id = ?");
        $stmt->execute([$prodId]);
        Helpers::setFlash('success', 'Product visibility toggled successfully!');
    } elseif ($action === 'delete') {
        $prodId = trim($_POST['product_id'] ?? '');
        if ($prodId) {
            try {
                $pdo->prepare("DELETE ci FROM combo_items ci INNER JOIN variants v ON v.id = ci.combo_variant_id OR v.id = ci.component_variant_id WHERE v.product_id = ?")->execute([$prodId]);
            } catch (Throwable $e) {}
            try {
                $pdo->prepare("UPDATE order_items SET variant_id = NULL WHERE variant_id IN (SELECT id FROM variants WHERE product_id = ?)")->execute([$prodId]);
            } catch (Throwable $e) {}
            $pdo->prepare("DELETE FROM variants WHERE product_id = ?")->execute([$prodId]);
            $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$prodId]);
            Helpers::setFlash('success', 'Product permanently deleted successfully!');
        }
    } elseif ($action === 'bulk_delete') {
        $ids = $_POST['product_ids'] ?? [];
        if (!empty($ids) && is_array($ids)) {
            $deletedCount = 0;
            foreach ($ids as $prodId) {
                $prodId = trim((string)$prodId);
                if (!$prodId) continue;
                try {
                    $pdo->prepare("DELETE ci FROM combo_items ci INNER JOIN variants v ON v.id = ci.combo_variant_id OR v.id = ci.component_variant_id WHERE v.product_id = ?")->execute([$prodId]);
                } catch (Throwable $e) {}
                try {
                    $pdo->prepare("UPDATE order_items SET variant_id = NULL WHERE variant_id IN (SELECT id FROM variants WHERE product_id = ?)")->execute([$prodId]);
                } catch (Throwable $e) {}
                $pdo->prepare("DELETE FROM variants WHERE product_id = ?")->execute([$prodId]);
                $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$prodId]);
                $deletedCount++;
            }
            Helpers::setFlash('success', "{$deletedCount} product(s) permanently deleted successfully!");
        }
    } elseif ($action === 'bulk_archive') {
        $ids = $_POST['product_ids'] ?? [];
        if (!empty($ids) && is_array($ids)) {
            $in = str_repeat('?,', count($ids) - 1) . '?';
            $stmt = $pdo->prepare("UPDATE products SET active = 0 WHERE id IN ($in)");
            $stmt->execute(array_values($ids));
            Helpers::setFlash('success', count($ids) . ' product(s) archived successfully.');
        }
    } elseif ($action === 'bulk_activate') {
        $ids = $_POST['product_ids'] ?? [];
        if (!empty($ids) && is_array($ids)) {
            $in = str_repeat('?,', count($ids) - 1) . '?';
            $stmt = $pdo->prepare("UPDATE products SET active = 1 WHERE id IN ($in)");
            $stmt->execute(array_values($ids));
            Helpers::setFlash('success', count($ids) . ' product(s) activated successfully.');
        }
    } elseif ($action === 'toggle_featured') {
        $prodId = trim($_POST['product_id'] ?? '');
        if ($prodId) {
            $res = Models::toggleProductFeatured($prodId);
            if ($res['success']) {
                Helpers::setFlash('success', $res['message']);
            } else {
                Helpers::setFlash('error', $res['message']);
            }
        }
    } elseif ($action === 'move_top') {
        $prodId = trim($_POST['product_id'] ?? '');
        if ($prodId) {
            Models::moveProductToTop($prodId);
            Helpers::setFlash('success', 'Product moved to top successfully!');
        }
    } elseif ($action === 'move_up') {
        $prodId = trim($_POST['product_id'] ?? '');
        if ($prodId) {
            Models::moveProductOrder($prodId, 'up');
            Helpers::setFlash('success', 'Product shifted up.');
        }
    } elseif ($action === 'move_down') {
        $prodId = trim($_POST['product_id'] ?? '');
        if ($prodId) {
            Models::moveProductOrder($prodId, 'down');
            Helpers::setFlash('success', 'Product shifted down.');
        }
    } elseif ($action === 'set_order') {
        $prodId = trim($_POST['product_id'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        if ($prodId) {
            Models::setProductSortOrder($prodId, $sortOrder);
            Helpers::setFlash('success', 'Product sort order updated.');
        }
    } elseif ($action === 'update_home_limit') {
        $limit = max(1, min(100, (int)($_POST['home_featured_limit'] ?? 8)));
        Models::setHomeFeaturedLimit($limit);
        Helpers::setFlash('success', "Home page display limit updated to {$limit} products!");
    }
    Helpers::redirect(Helpers::url('admin/products' . (!empty($_GET['home']) ? '?home=1' : '')));
}

$q = trim($_GET['q'] ?? '');
$selectedCategory = trim($_GET['category'] ?? '');
$homeFilter = trim($_GET['home'] ?? '');
$homeFeaturedLimit = Models::getHomeFeaturedLimit();

$allCategories = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC, name ASC")->fetchAll();

$querySql = "
    SELECT p.*, c.name AS category_name, c.slug AS category_slug 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.sort_order ASC, p.name ASC
";
$products = $pdo->query($querySql)->fetchAll();

// Batch attach variants to all products without N+1 query loops
$allItems = [];
if (!empty($products)) {
    $prodIds = array_column($products, 'id');
    $placeholders = implode(',', array_fill(0, count($prodIds), '?'));
    $varStmt = $pdo->prepare("SELECT * FROM variants WHERE product_id IN ($placeholders) ORDER BY price_paise ASC");
    $varStmt->execute($prodIds);
    $allVariants = $varStmt->fetchAll();

    $variantMap = [];
    foreach ($allVariants as $v) {
        $variantMap[$v['product_id']][] = $v;
    }

    foreach ($products as $p) {
        $p['variants'] = $variantMap[$p['id']] ?? [];
        $allItems[] = $p;
    }
}


$homeFeaturedCount = count(array_filter($allItems, fn($p) => !empty($p['is_featured']) && !empty($p['active'])));

// In-memory filter matching Node.js
$filtered = array_filter($allItems, function ($p) use ($q, $selectedCategory, $homeFilter) {
    if ($homeFilter === '1' && empty($p['is_featured'])) {
        return false;
    }
    if ($q !== '') {
        $qLower = strtolower($q);
        $matchName = strpos(strtolower($p['name']), $qLower) !== false;
        $matchSlug = strpos(strtolower($p['slug']), $qLower) !== false;
        $matchSku = false;
        foreach ($p['variants'] as $v) {
            if (strpos(strtolower($v['sku']), $qLower) !== false) {
                $matchSku = true;
                break;
            }
        }
        if (!$matchName && !$matchSlug && !$matchSku) {
            return false;
        }
    }
    if ($selectedCategory !== '') {
        if (($p['category_slug'] ?? '') !== $selectedCategory) {
            return false;
        }
    }
    return true;
});

// KPI Metrics
$totalProducts = count($allItems);
$totalStockUnits = 0;
$lowStockCount = 0;
foreach ($allItems as $p) {
    $hasLow = false;
    foreach ($p['variants'] as $v) {
        $totalStockUnits += (int)$v['stock'];
        if ((int)$v['stock'] <= (int)$v['low_stock_threshold'] && (int)$v['stock'] > 0) {
            $hasLow = true;
        }
    }
    if ($hasLow) {
        $lowStockCount++;
    }
}

$pageTitle = 'Products & Inventory';
$activeNav = 'products';
require __DIR__ . '/../layouts/admin_layout.php';
?>

<div style="max-width: 1240px; margin: 0 auto; padding: 28px 24px 80px;">
  <!-- Header Banner -->
  <div class="admin-v3-header-wrap">
    <div class="admin-v3-header-left">
      <div class="admin-v3-breadcrumbs">
        <a href="<?= Helpers::url('admin') ?>">Dashboard</a>
        <span aria-hidden="true">/</span>
        <span>Products &amp; Inventory</span>
      </div>
      <h1 class="admin-v3-title">
        Products &amp; <em>Inventory</em>
      </h1>
      <p class="admin-v3-subtitle">
        Easily manage pack sizes, selling prices in INR, inventory stock, and product status.
      </p>
    </div>

    <div class="admin-v3-header-script" aria-hidden="true">
      <span class="admin-v3-script-text">
        Real Ingredients.<br>
        Brighter Days.
      </span>
    </div>
  </div>

  <!-- 4 KPI Metric Cards -->
  <div class="admin-v3-kpi-grid">
    <!-- Card 1: Total Products -->
    <div class="admin-v3-kpi-card">
      <div class="admin-v3-kpi-left">
        <div class="admin-v3-kpi-icon-box" style="background: #EAF5EC; color: #2E7D32;">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
            <path d="m3.3 7 8.7 5 8.7-5" />
            <path d="M12 22V12" />
          </svg>
        </div>
        <div class="admin-v3-kpi-info">
          <span class="admin-v3-kpi-num"><?= $totalProducts ?></span>
          <span class="admin-v3-kpi-label">TOTAL PRODUCTS</span>
        </div>
      </div>
      <img src="<?= Helpers::asset('singlebadam.png') ?>" alt="Badam" class="admin-kpi-thumb">
    </div>

    <!-- Card 2: Total Stock Units -->
    <div class="admin-v3-kpi-card">
      <div class="admin-v3-kpi-left">
        <div class="admin-v3-kpi-icon-box" style="background: #F7F1E5; color: #8E6D38;">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <ellipse cx="12" cy="5" rx="9" ry="3" />
            <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3" />
            <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" />
          </svg>
        </div>
        <div class="admin-v3-kpi-info">
          <span class="admin-v3-kpi-num"><?= number_format($totalStockUnits) ?></span>
          <span class="admin-v3-kpi-label">TOTAL STOCK UNITS</span>
        </div>
      </div>
      <img src="<?= Helpers::asset('singlepistaproduct.png') ?>" alt="Pista" class="admin-kpi-thumb">
    </div>

    <!-- Card 3: Low-Stock Warnings -->
    <div class="admin-v3-kpi-card">
      <div class="admin-v3-kpi-left">
        <div class="admin-v3-kpi-icon-box" style="background: #FEF6E9; color: #B4691B;">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
            <line x1="12" y1="9" x2="12" y2="13" />
            <line x1="12" y1="17" x2="12.01" y2="17" />
          </svg>
        </div>
        <div class="admin-v3-kpi-info">
          <span class="admin-v3-kpi-num" style="color: <?= $lowStockCount > 0 ? '#B4691B' : '#1E2520' ?>;">
            <?= $lowStockCount ?>
          </span>
          <span class="admin-v3-kpi-label">LOW-STOCK WARNINGS</span>
        </div>
      </div>
      <img src="<?= Helpers::asset('acrod image.png') ?>" alt="Akhrot" class="admin-kpi-thumb">
    </div>

    <!-- Card 4: Catalog Categories -->
    <div class="admin-v3-kpi-card">
      <div class="admin-v3-kpi-left">
        <div class="admin-v3-kpi-icon-box" style="background: #EAF5EC; color: #2E7D32;">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" />
            <line x1="7" y1="7" x2="7.01" y2="7" />
          </svg>
        </div>
        <div class="admin-v3-kpi-info">
          <span class="admin-v3-kpi-num"><?= count($allCategories) ?></span>
          <span class="admin-v3-kpi-label">CATALOG CATEGORIES</span>
        </div>
      </div>
      <img src="<?= Helpers::asset('drydate image.png') ?>" alt="Dry Dates" class="admin-kpi-thumb">
    </div>
  </div>

  <!-- Quick Collection Filter & Home Page Display Limit Bar -->
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 12px; background: #FAF8F5; border: 1.5px solid #E5DFD5; border-radius: 10px; padding: 12px 16px;">
    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
      <a href="<?= Helpers::url('admin/products') ?>" style="padding: 7px 16px; border-radius: 20px; font-size: 13px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; <?= empty($homeFilter) ? 'background: #073D26; color: #fff;' : 'background: #fff; color: #5C5549; border: 1px solid #D6C7B2;' ?>">
        <span>All Products (<?= count($allItems) ?>)</span>
      </a>
      <a href="<?= Helpers::url('admin/products?home=1') ?>" style="padding: 7px 16px; border-radius: 20px; font-size: 13px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; <?= $homeFilter === '1' ? 'background: #B4691B; color: #fff;' : 'background: #FDF9F0; color: #8C5311; border: 1.5px solid #E6D2B5;' ?>">
        <span>⭐ Home Page Products (<?= $homeFeaturedCount ?>)</span>
      </a>
    </div>

    <!-- Home Page Display Limit Input Field -->
    <form method="POST" style="display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap;">
      <?= Helpers::csrfField() ?>
      <input type="hidden" name="action" value="update_home_limit">
      <label for="home_featured_limit" style="font-size: 13px; font-weight: 700; color: #073D26; margin: 0;">
        🏠 Home Page Display Count:
      </label>
      <input
        type="number"
        id="home_featured_limit"
        name="home_featured_limit"
        min="1"
        max="50"
        value="<?= $homeFeaturedLimit ?>"
        style="width: 58px; padding: 5px 8px; font-size: 13.5px; font-weight: 700; border: 1.5px solid #073D26; border-radius: 6px; text-align: center; background: #fff;"
      >
      <span style="font-size: 12.5px; color: #5C5549;">products</span>
      <button type="submit" class="button button-primary" style="padding: 6px 14px; font-size: 12px; font-weight: 700; border-radius: 6px;">
        Save Count
      </button>
    </form>
  </div>

  <!-- Search & Filter Toolbar -->
  <form class="admin-v3-toolbar" method="GET" action="<?= Helpers::url('admin/products') ?>">
    <?php if ($homeFilter === '1'): ?>
      <input type="hidden" name="home" value="1">
    <?php endif; ?>
    <div class="admin-v3-search-wrap">
      <span class="admin-v3-search-icon">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8" />
          <line x1="21" y1="21" x2="16.65" y2="16.65" />
        </svg>
      </span>
      <input
        name="q"
        value="<?= htmlspecialchars($q) ?>"
        placeholder="Search by product name, slug, or SKU…"
        class="admin-v3-search-input"
      >
    </div>

    <select name="category" class="admin-v3-cat-select">
      <option value="">All Categories</option>
      <?php foreach ($allCategories as $c): ?>
        <option value="<?= htmlspecialchars($c['slug']) ?>" <?= $selectedCategory === $c['slug'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button type="submit" class="admin-v3-btn-filter">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
      </svg>
      <span>Filter</span>
    </button>

    <a href="<?= Helpers::url('admin/categories') ?>" class="admin-v3-btn-categories">
      Manage Categories
    </a>

    <a href="<?= Helpers::url('admin/products/new') ?>" class="admin-v3-btn-add">
      <span>+ Add New Product</span>
    </a>
  </form>

  <!-- Products Table Card -->
  <div class="admin-v3-table-card">
    <div style="overflow-x: auto;">
      <table class="admin-v3-table">
        <thead>
          <tr>
            <th style="width: 44px; text-align: center;">
              <input type="checkbox" id="selectAllProducts" class="admin-v3-checkbox" aria-label="Select all products" onchange="toggleSelectAll(this)">
            </th>
            <th style="min-width: 210px;">PRODUCT</th>
            <th>CATEGORY</th>
            <th>MOVE TO TOP</th>
            <th style="text-align: center;">HOME PAGE</th>
            <th>PACK SIZES</th>
            <th>PRICE RANGE</th>
            <th>STOCK UNITS</th>
            <th>STATUS</th>
            <th style="text-align: right; min-width: 200px;">ACTIONS</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($filtered)): ?>
            <tr>
              <td colspan="10" style="text-align: center; padding: 48px 20px; color: #7C7265;">
                No products found matching your search.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($filtered as $p): 
              $pVariants = $p['variants'];
              $totalStock = array_reduce($pVariants, fn($sum, $v) => $sum + (int)$v['stock'], 0);
              $isLow = false;
              foreach ($pVariants as $v) {
                  if ((int)$v['stock'] <= (int)$v['low_stock_threshold'] && (int)$v['stock'] > 0) {
                      $isLow = true;
                      break;
                  }
              }

              $prices = array_map(fn($v) => (int)$v['price_paise'], $pVariants);
              $minPrice = !empty($prices) ? min($prices) : 0;
              $maxPrice = !empty($prices) ? max($prices) : 0;

              // Determine status
              $statusClass = "admin-v3-status-active";
              $statusLabel = "ACTIVE";
              $statusDotColor = "#2E7D32";

              if (!$p['active']) {
                  $statusClass = "admin-v3-status-archived";
                  $statusLabel = "ARCHIVED";
                  $statusDotColor = "#7A7264";
              } elseif ($totalStock === 0) {
                  $statusClass = "admin-v3-status-out";
                  $statusLabel = "OUT OF STOCK";
                  $statusDotColor = "#A73A2A";
              } elseif ($isLow) {
                  $statusClass = "admin-v3-status-low";
                  $statusLabel = "LOW STOCK";
                  $statusDotColor = "#B4691B";
              }

              // Determine stock pill
              $stockClass = "admin-v3-stock-in";
              $stockText = "{$totalStock} IN STOCK";
              if ($totalStock === 0) {
                  $stockClass = "admin-v3-stock-out";
                  $stockText = "OUT OF STOCK";
              } elseif ($isLow) {
                  $stockClass = "admin-v3-stock-low";
                  $stockText = "{$totalStock} IN STOCK";
              }
            ?>
              <tr>
                <!-- Checkbox -->
                <td style="text-align: center;">
                  <input type="checkbox" name="selected_products[]" value="<?= $p['id'] ?>" class="admin-v3-checkbox product-select-checkbox" data-product-name="<?= htmlspecialchars($p['name']) ?>" onchange="updateBulkBar()" aria-label="Select <?= htmlspecialchars($p['name']) ?>">
                </td>

                <!-- Product Thumbnail & Name -->
                <td>
                  <div class="admin-v3-product-cell">
                    <img
                      src="<?= Helpers::asset(ltrim($p['image_url'] ?: 'shop-cashews.jpg', '/')) ?>"
                      alt="<?= htmlspecialchars($p['name']) ?>"
                      class="admin-v3-product-thumb"
                    >
                    <div>
                      <span class="admin-v3-product-name"><?= htmlspecialchars($p['name']) ?></span>
                      <span class="admin-v3-product-slug">/<?= htmlspecialchars($p['slug']) ?></span>
                    </div>
                  </div>
                </td>

                <!-- Category -->
                <td>
                  <span class="admin-v3-cat-pill">
                    <?= strtoupper(htmlspecialchars($p['category_name'] ?? 'GENERAL')) ?>
                  </span>
                </td>

                <!-- Move to Top only -->
                <td style="white-space: nowrap;">
                  <form method="POST" style="display:inline;" title="Move product to top of catalog">
                    <?= Helpers::csrfField() ?>
                    <input type="hidden" name="action" value="move_top">
                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                    <button type="submit" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; font-size: 11.5px; background: #073D26; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: 700; box-shadow: 0 1px 2px rgba(0,0,0,0.06);">
                      <span>⬆</span>
                      <span>Top</span>
                    </button>
                  </form>
                </td>

                <!-- Featured on Home Page Toggle -->
                <td style="text-align: center; white-space: nowrap;">
                  <form method="POST" style="display:inline;">
                    <?= Helpers::csrfField() ?>
                    <input type="hidden" name="action" value="toggle_featured">
                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                    <?php if (!empty($p['is_featured'])): ?>
                      <button type="submit" style="display: inline-flex; align-items: center; gap: 5px; background: #E8F5E9; border: 1.5px solid #2E7D32; color: #1B5E20; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700; cursor: pointer;" title="Showing on Home Page (Click to remove)">
                        <span>⭐</span>
                        <span>Home</span>
                      </button>
                    <?php else: ?>
                      <button type="submit" style="display: inline-flex; align-items: center; gap: 4px; background: #FAF8F5; border: 1px solid #D5CEBF; color: #8A7E6F; padding: 4px 8px; border-radius: 20px; font-size: 11.5px; font-weight: 600; cursor: pointer;" title="Show on Home Page">
                        <span>☆</span>
                        <span>Add</span>
                      </button>
                    <?php endif; ?>
                  </form>
                </td>

                <!-- Pack Sizes -->
                <td>
                  <div class="admin-v3-sizes-row">
                    <?php if (empty($pVariants)): ?>
                      <span style="font-size: 11px; color: #8C8477;">—</span>
                    <?php else: ?>
                      <?php foreach ($pVariants as $v): ?>
                        <span class="admin-v3-size-pill">
                          <?= htmlspecialchars($v['label']) ?>
                        </span>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                </td>

                <!-- Price Range -->
                <td>
                  <span class="admin-v3-price-range">
                    <?= $minPrice === $maxPrice ? Helpers::formatPaise($minPrice) : Helpers::formatPaise($minPrice) . ' – ' . Helpers::formatPaise($maxPrice) ?>
                  </span>
                </td>

                <!-- Stock Units -->
                <td>
                  <span class="admin-v3-stock-pill <?= $stockClass ?>">
                    <?= $stockText ?>
                  </span>
                </td>

                <!-- Status -->
                <td>
                  <span class="admin-v3-status-pill <?= $statusClass ?>">
                    <span class="admin-v3-status-dot" style="background: <?= $statusDotColor ?>;"></span>
                    <span><?= $statusLabel ?></span>
                  </span>
                </td>

                <!-- Actions -->
                <td style="text-align: right;">
                  <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center; flex-wrap: wrap;">
                    <a href="<?= Helpers::url('admin/products/edit?id=' . $p['id']) ?>" class="button button-outline" style="font-size: 11.5px; padding: 4px 8px; background: #fff; color: #1E2520;">
                      Edit
                    </a>
                    <a href="<?= Helpers::url('products/' . $p['slug']) ?>" target="_blank" class="button button-outline" style="font-size: 11.5px; padding: 4px 8px; background: #fff; color: #073D26;">
                      View ↗
                    </a>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Toggle status for <?= htmlspecialchars(addslashes($p['name'])) ?>?');">
                      <?= Helpers::csrfField() ?>
                      <input type="hidden" name="action" value="toggle_active">
                      <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                      <button type="submit" class="button button-outline" style="font-size: 11.5px; padding: 4px 8px; background: #fff; color: #7D7567;">
                        <?= $p['active'] ? 'Archive' : 'Restore' ?>
                      </button>
                    </form>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Permanently delete \'<?= htmlspecialchars(addslashes($p['name'])) ?>\'? This action cannot be undone.');">
                      <?= Helpers::csrfField() ?>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                      <button type="submit" class="button button-outline" style="font-size: 11.5px; padding: 4px 8px; background: #FFF5F5; color: #C53030; border-color: #FEB2B2;" title="Permanently delete product">
                        Delete
                      </button>
                    </form>

                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Table Footer / Pagination -->
    <div class="admin-v3-table-footer">
      <span>
        Showing 1–<?= count($filtered) ?> of <?= $totalProducts ?> products
      </span>

      <div class="admin-v3-pagination">
        <button type="button" class="admin-v3-page-btn" aria-label="Previous page">
          ‹
        </button>
        <button type="button" class="admin-v3-page-btn admin-v3-page-active">
          1
        </button>
        <button type="button" class="admin-v3-page-btn" aria-label="Next page">
          ›
        </button>
      </div>
    </div>
  </div>

  <!-- Floating Bulk Actions Bar -->
  <div id="bulkActionBar" style="display: none; position: sticky; bottom: 20px; z-index: 1000; margin: 20px 0 0; background: #073D26; color: #fff; padding: 14px 22px; border-radius: 10px; box-shadow: 0 12px 30px rgba(0,0,0,0.3); align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; border: 1px solid #1C593E;">
    <div style="display: flex; align-items: center; gap: 12px;">
      <span id="selectedCountBadge" style="background: #E2C376; color: #073D26; font-weight: 800; font-size: 14px; padding: 3px 12px; border-radius: 20px;">0</span>
      <span style="font-size: 14px; font-weight: 600; letter-spacing: 0.2px;">Products Selected</span>
    </div>
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
      <button type="button" onclick="submitBulk('bulk_delete')" style="background: #C53030; color: #fff; border: none; padding: 9px 18px; border-radius: 6px; font-size: 13px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: background 0.2s;" onmouseover="this.style.background='#9B2C2C'" onmouseout="this.style.background='#C53030'">
        🗑️ Delete Selected
      </button>
      <button type="button" onclick="submitBulk('bulk_archive')" style="background: #1A4D36; color: #EDE8DE; border: 1px solid #336D50; padding: 9px 15px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
        📦 Archive Selected
      </button>
      <button type="button" onclick="submitBulk('bulk_activate')" style="background: #1A4D36; color: #EDE8DE; border: 1px solid #336D50; padding: 9px 15px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
        ✓ Activate Selected
      </button>
      <button type="button" onclick="deselectAll()" style="background: transparent; color: #D1C8B8; border: none; padding: 9px 12px; font-size: 13px; cursor: pointer; text-decoration: underline;">
        Cancel
      </button>
    </div>
  </div>

  <!-- Hidden Form for Bulk Actions -->
  <form id="bulkActionForm" method="POST" style="display: none;">
    <?= Helpers::csrfField() ?>
    <input type="hidden" name="action" id="bulkFormAction" value="">
    <div id="bulkFormInputs"></div>
  </form>

</div>

<script>
  function toggleSelectAll(master) {
    const checkboxes = document.querySelectorAll('.product-select-checkbox');
    checkboxes.forEach(cb => cb.checked = master.checked);
    updateBulkBar();
  }

  function updateBulkBar() {
    const checkedBoxes = document.querySelectorAll('.product-select-checkbox:checked');
    const allBoxes = document.querySelectorAll('.product-select-checkbox');
    const count = checkedBoxes.length;
    const bar = document.getElementById('bulkActionBar');
    const badge = document.getElementById('selectedCountBadge');
    const master = document.getElementById('selectAllProducts');

    if (badge) badge.textContent = count;
    if (bar) bar.style.display = count > 0 ? 'flex' : 'none';

    if (master) {
      master.checked = count > 0 && count === allBoxes.length;
      master.indeterminate = count > 0 && count < allBoxes.length;
    }
  }

  function deselectAll() {
    const checkboxes = document.querySelectorAll('.product-select-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    const master = document.getElementById('selectAllProducts');
    if (master) {
      master.checked = false;
      master.indeterminate = false;
    }
    updateBulkBar();
  }

  function submitBulk(action) {
    const checkedBoxes = document.querySelectorAll('.product-select-checkbox:checked');
    const count = checkedBoxes.length;
    if (count === 0) return;

    let msg = '';
    if (action === 'bulk_delete') {
      msg = `Are you sure you want to PERMANENTLY delete the ${count} selected product(s)? This will also remove their pack sizes and cannot be undone.`;
    } else if (action === 'bulk_archive') {
      msg = `Archive ${count} selected product(s)? They will be hidden from the storefront.`;
    } else if (action === 'bulk_activate') {
      msg = `Activate ${count} selected product(s)? They will become visible on the storefront.`;
    }

    if (confirm(msg)) {
      const form = document.getElementById('bulkActionForm');
      const actionInput = document.getElementById('bulkFormAction');
      const container = document.getElementById('bulkFormInputs');
      actionInput.value = action;
      container.innerHTML = '';
      checkedBoxes.forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'product_ids[]';
        input.value = cb.value;
        container.appendChild(input);
      });
      form.submit();
    }
  }
</script>

<?php require __DIR__ . '/../layouts/admin_layout_bottom.php'; ?>
