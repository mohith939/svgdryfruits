<?php
/**
 * SVG Dry Fruits - Coupons & Offers Management
 * 1:1 match of Node.js src/app/admin/coupons/page.tsx
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Models.php';
require_once ROOT_PATH . '/src/Auth.php';

Auth::requireAuth();

$pdo = Database::getConnection();

// Handle coupon creation, update, and deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Helpers::requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type = trim($_POST['type'] ?? 'percentage');
        $val = (float)($_POST['value'] ?? 0);
        $minPaise = (int)round((float)($_POST['min_subtotal_inr'] ?? 0) * 100);
        $active = isset($_POST['active']) ? 1 : 0;

        if ($type === 'percentage') {
            $numVal = (int)round($val);
        } elseif ($type === 'fixed') {
            $numVal = (int)round($val * 100);
        } else {
            $numVal = 0; // free shipping
        }

        if ($code) {
            $id = 'cpn_' . bin2hex(random_bytes(8));
            $stmt = $pdo->prepare("
                INSERT INTO coupons (id, code, name, description, type, value, min_subtotal_paise, active, times_used)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
            ");
            $stmt->execute([$id, $code, $name ?: null, $description ?: null, $type, $numVal, $minPaise, $active]);
            Helpers::setFlash('success', "Coupon {$code} created successfully!");
        } else {
            Helpers::setFlash('error', "Coupon code is required.");
        }
    } elseif ($action === 'toggle_active') {
        $id = trim($_POST['id'] ?? '');
        $stmt = $pdo->prepare("UPDATE coupons SET active = 1 - active WHERE id = ?");
        $stmt->execute([$id]);
        Helpers::setFlash('success', "Coupon active status changed.");
    } elseif ($action === 'delete') {
        $id = trim($_POST['id'] ?? '');
        $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->execute([$id]);
        Helpers::setFlash('success', "Coupon removed successfully.");
    }

    Helpers::redirect(Helpers::url('admin/coupons'));
}

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');

// Fetch all coupons
$coupons = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();

$filtered = array_filter($coupons, function ($c) use ($search, $statusFilter) {
    if ($search !== '') {
        $q = strtolower($search);
        $matchCode = strpos(strtolower($c['code']), $q) !== false;
        $matchName = strpos(strtolower($c['name'] ?? ''), $q) !== false;
        if (!$matchCode && !$matchName) return false;
    }
    if ($statusFilter === 'active' && !$c['active']) return false;
    if ($statusFilter === 'expired' && $c['active']) return false;
    return true;
});

// KPI Metrics
$totalCoupons = count($coupons);
$activeCoupons = count(array_filter($coupons, fn($c) => !empty($c['active'])));
$scheduledCoupons = 0;
$expiredCoupons = count(array_filter($coupons, fn($c) => empty($c['active'])));

$pageTitle = 'Coupons & Offers';
$activeNav = 'coupons';
require __DIR__ . '/../layouts/admin_layout.php';
?>

<div class="coupon-v3-page-wrap">
  <!-- Header Banner matching Node.js -->
  <div class="coupon-v3-header-wrap">
    <div class="coupon-v3-header-left">
      <div class="admin-v3-breadcrumbs">
        <a href="<?= Helpers::url('admin') ?>">Dashboard</a>
        <span aria-hidden="true">/</span>
        <span>Coupons</span>
      </div>
      <h1 class="coupon-v3-title">
        Coupons &amp; <em>Offers</em>
      </h1>
      <p class="coupon-v3-subtitle">
        Create and manage discount coupons to boost sales and reward your customers.
      </p>
    </div>

    <div class="admin-v3-header-right">
      <button
        type="button"
        onclick="document.getElementById('inlineCouponForm').style.display='block'"
        class="admin-v3-btn-create-order"
      >
        <span aria-hidden="true" style="font-size: 16px; font-weight: 700;">+</span> Create Coupon
      </button>
      <a
        href="#"
        onclick="window.print(); return false;"
        class="admin-v3-btn-export"
        style="text-decoration:none;"
      >
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
          <polyline points="7 10 12 15 17 10" />
          <line x1="12" y1="15" x2="12" y2="3" />
        </svg>
        Print / Export
      </a>
    </div>
  </div>

  <!-- 4 KPI Metric Cards -->
  <div class="admin-v3-kpi-grid">
    <!-- Card 1: Total Coupons -->
    <div class="admin-v3-kpi-card">
      <div class="admin-v3-kpi-left">
        <div class="admin-v3-kpi-icon-box" style="background: #EAF5EC; color: #2E7D32;">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" />
            <line x1="7" y1="7" x2="7.01" y2="7" />
          </svg>
        </div>
        <div class="admin-v3-kpi-info">
          <span class="admin-v3-kpi-num"><?= $totalCoupons ?></span>
          <span class="admin-v3-kpi-label">TOTAL COUPONS</span>
        </div>
      </div>
      <img src="<?= Helpers::asset('singlebadam.png') ?>" alt="" class="admin-kpi-thumb">
    </div>

    <!-- Card 2: Active Coupons -->
    <div class="admin-v3-kpi-card">
      <div class="admin-v3-kpi-left">
        <div class="admin-v3-kpi-icon-box" style="background: #F7F1E5; color: #8E6D38;">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="5" width="20" height="14" rx="2" />
            <line x1="2" y1="10" x2="22" y2="10" />
          </svg>
        </div>
        <div class="admin-v3-kpi-info">
          <span class="admin-v3-kpi-num"><?= $activeCoupons ?></span>
          <span class="admin-v3-kpi-label">ACTIVE COUPONS</span>
        </div>
      </div>
      <img src="<?= Helpers::asset('singlepistaproduct.png') ?>" alt="" class="admin-kpi-thumb">
    </div>

    <!-- Card 3: Scheduled Coupons -->
    <div class="admin-v3-kpi-card">
      <div class="admin-v3-kpi-left">
        <div class="admin-v3-kpi-icon-box" style="background: #FEF6E9; color: #B4691B;">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10" />
            <polyline points="12 6 12 12 16 14" />
          </svg>
        </div>
        <div class="admin-v3-kpi-info">
          <span class="admin-v3-kpi-num"><?= $scheduledCoupons ?></span>
          <span class="admin-v3-kpi-label">SCHEDULED COUPONS</span>
        </div>
      </div>
      <img src="<?= Helpers::asset('acrod image.png') ?>" alt="" class="admin-kpi-thumb">
    </div>

    <!-- Card 4: Expired Coupons -->
    <div class="admin-v3-kpi-card">
      <div class="admin-v3-kpi-left">
        <div class="admin-v3-kpi-icon-box" style="background: #FDF0ED; color: #C24B38;">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10" />
            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07" />
          </svg>
        </div>
        <div class="admin-v3-kpi-info">
          <span class="admin-v3-kpi-num"><?= $expiredCoupons ?></span>
          <span class="admin-v3-kpi-label">EXPIRED COUPONS</span>
        </div>
      </div>
      <img src="<?= Helpers::asset('drydate image.png') ?>" alt="" class="admin-kpi-thumb">
    </div>
  </div>

  <!-- Search & Filter Toolbar -->
  <form class="admin-v3-toolbar" method="GET" action="<?= Helpers::url('admin/coupons') ?>">
    <div class="admin-v3-search-wrap">
      <span class="admin-v3-search-icon">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8" />
          <line x1="21" y1="21" x2="16.65" y2="16.65" />
        </svg>
      </span>
      <input
        type="text"
        name="search"
        value="<?= htmlspecialchars($search) ?>"
        placeholder="Search by coupon code, name, or description..."
        class="admin-v3-search-input"
      >
    </div>

    <select name="status" class="admin-v3-cat-select">
      <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
      <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="expired" <?= $statusFilter === 'expired' ? 'selected' : '' ?>>Expired / Inactive</option>
    </select>

    <button
      type="button"
      onclick="document.getElementById('inlineCouponForm').style.display='block'"
      class="admin-v3-btn-create"
    >
      <span aria-hidden="true">+</span> Create Coupon
    </button>
  </form>

  <!-- Inline Create Coupon Form -->
  <div id="inlineCouponForm" class="coupon-v3-inline-create-box" style="display: none; background: #fff; border: 1px solid #D6C8B5; border-radius: 12px; padding: 24px; margin-bottom: 24px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid #ECE7DE; padding-bottom: 14px;">
      <div>
        <h2 style="font-family: 'Playfair Display', serif; font-size: 20px; color: #143D24; margin: 0; font-weight: 600;">
          Create New Discount Coupon
        </h2>
        <p style="font-size: 13px; color: #6B6255; margin: 4px 0 0;">
          Fill in the coupon details below to make it available for customer redemptions immediately.
        </p>
      </div>
      <button
        type="button"
        onclick="document.getElementById('inlineCouponForm').style.display='none'"
        style="background: none; border: none; font-size: 20px; color: #8C8476; cursor: pointer; padding: 4px 8px;"
      >
        ✕
      </button>
    </div>

    <form method="POST" class="coupon-v3-modal-form">
      <?= Helpers::csrfField() ?>
      <input type="hidden" name="action" value="create">

      <div class="coupon-v3-modal-grid">
        <div class="coupon-v3-field">
          <label class="coupon-v3-label">Coupon Code *</label>
          <input
            type="text"
            name="code"
            required
            placeholder="e.g. SVGDIWALI"
            class="coupon-v3-input"
            style="text-transform: uppercase; font-weight: 700;"
          >
        </div>

        <div class="coupon-v3-field">
          <label class="coupon-v3-label">Coupon Name / Campaign</label>
          <input
            type="text"
            name="name"
            placeholder="e.g. Diwali Festive Offer"
            class="coupon-v3-input"
          >
        </div>
      </div>

      <div class="coupon-v3-field" style="margin-top: 12px;">
        <label class="coupon-v3-label">Description (Shown to shoppers in cart)</label>
        <input
          type="text"
          name="description"
          placeholder="e.g. Flat 15% discount on all orders above ₹500"
          class="coupon-v3-input"
        >
      </div>

      <div class="coupon-v3-modal-grid" style="margin-top: 12px;">
        <div class="coupon-v3-field">
          <label class="coupon-v3-label">Discount Type *</label>
          <select name="type" class="coupon-v3-input">
            <option value="percentage">Percentage (%)</option>
            <option value="fixed">Fixed Amount (₹)</option>
            <option value="free_shipping">Free Shipping</option>
          </select>
        </div>

        <div class="coupon-v3-field">
          <label class="coupon-v3-label">Discount Value *</label>
          <input
            type="number"
            name="value"
            min="0"
            value="10"
            required
            class="coupon-v3-input"
          >
        </div>
      </div>

      <div class="coupon-v3-modal-grid" style="margin-top: 12px;">
        <div class="coupon-v3-field">
          <label class="coupon-v3-label">Minimum Order Value (₹) *</label>
          <input
            type="number"
            name="min_subtotal_inr"
            min="0"
            value="500"
            required
            class="coupon-v3-input"
          >
        </div>

        <div class="coupon-v3-field" style="display: flex; align-items: center; margin-top: 24px;">
          <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
            <input type="checkbox" name="active" value="1" checked class="admin-v3-checkbox">
            <span style="font-size: 13.5px; font-weight: 500; color: #1E2520;">
              Active and available for checkout
            </span>
          </label>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #ECE7DE; padding-top: 16px; margin-top: 16px;">
        <button
          type="button"
          onclick="document.getElementById('inlineCouponForm').style.display='none'"
          class="coupon-v3-modal-cancel"
        >
          Cancel
        </button>
        <button type="submit" class="coupon-v3-modal-submit">
          Create Coupon
        </button>
      </div>
    </form>
  </div>

  <!-- Coupons Table -->
  <div class="admin-v3-table-card">
    <div class="admin-v3-table-scroll">
      <table class="admin-v3-table">
        <thead>
          <tr>
            <th style="width: 42px; padding-left: 18px;">
              <input type="checkbox" class="admin-v3-checkbox" aria-label="Select all coupons">
            </th>
            <th>COUPON CODE</th>
            <th>COUPON NAME</th>
            <th>DISCOUNT TYPE</th>
            <th>DISCOUNT VALUE</th>
            <th>MIN. ORDER</th>
            <th>STATUS</th>
            <th style="text-align: right; padding-right: 24px;">ACTIONS</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($filtered)): ?>
            <tr>
              <td colspan="8" style="text-align: center; padding: 48px 20px; color: #7D7567;">
                No coupons found matching your criteria.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($filtered as $c): 
              $formattedVal = in_array($c['type'], ['fixed', 'flat'], true) 
                  ? Helpers::formatPaise((int)$c['value']) 
                  : (($c['type'] === 'free_shipping') ? 'Free Shipping' : $c['value'] . '%');
            ?>
              <tr>
                <td style="padding-left: 18px;">
                  <input type="checkbox" class="admin-v3-checkbox">
                </td>

                <!-- Code Badge -->
                <td>
                  <span class="coupon-v3-code-badge">
                    <?= htmlspecialchars($c['code']) ?>
                  </span>
                </td>

                <!-- Name & Description -->
                <td>
                  <div style="display: flex; flex-direction: column; gap: 2px;">
                    <span style="font-weight: 600; color: #1E2520; font-size: 13.5px;">
                      <?= htmlspecialchars($c['name'] ?: $c['code']) ?>
                    </span>
                    <?php if (!empty($c['description'])): ?>
                      <span style="font-size: 12px; color: #7D7567;">
                        <?= htmlspecialchars($c['description']) ?>
                      </span>
                    <?php endif; ?>
                  </div>
                </td>

                <!-- Discount Type Pill -->
                <td>
                  <?php if ($c['type'] === 'percentage'): ?>
                    <span class="coupon-v3-type-percentage">Percentage</span>
                  <?php elseif (in_array($c['type'], ['fixed', 'flat'], true)): ?>
                    <span class="coupon-v3-type-fixed">Fixed Amount</span>
                  <?php else: ?>
                    <span class="coupon-v3-type-shipping">Free Shipping</span>
                  <?php endif; ?>
                </td>

                <!-- Discount Value -->
                <td style="font-weight: 600; color: #1E2520; font-size: 13.5px;">
                  <?= $formattedVal ?>
                </td>

                <!-- Min Order -->
                <td style="color: #4A4033; font-size: 13.5px; font-weight: 500;">
                  <?= Helpers::formatPaise((int)$c['min_subtotal_paise']) ?>
                </td>

                <!-- Status -->
                <td>
                  <form method="POST" style="display:inline;">
                    <?= Helpers::csrfField() ?>
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                    <button
                      type="submit"
                      class="badge-toggle <?= $c['active'] ? 'badge-success' : 'badge-danger' ?>"
                      title="Toggle active"
                    >
                      <?= $c['active'] ? 'Active' : 'Inactive' ?>
                    </button>
                  </form>
                </td>

                <!-- Actions -->
                <td style="text-align: right; padding-right: 24px;">
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Delete coupon <?= htmlspecialchars(addslashes($c['code'])) ?>?');">
                    <?= Helpers::csrfField() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                    <button type="submit" class="admin-remove-btn">
                      Delete
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../layouts/admin_layout_bottom.php'; ?>
