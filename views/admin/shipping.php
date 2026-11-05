<?php
/**
 * SVG Dry Fruits - Multi-Condition Shipping Rates Management
 * Styled to match views/admin/coupons.php
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Models.php';

$pdo = Database::getConnection();

// Handle CRUD POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $estimatedDays = trim($_POST['estimated_days'] ?? '');
        $rateINR = (float)($_POST['rate_inr'] ?? 49);
        $freeINR = trim($_POST['free_above_inr'] ?? '');
        $minINR = (float)($_POST['min_subtotal_inr'] ?? 0);
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $active = isset($_POST['active']) ? 1 : 0;

        $ratePaise = (int)round($rateINR * 100);
        $freePaise = ($freeINR !== '') ? (int)round((float)$freeINR * 100) : null;
        $minPaise = (int)round($minINR * 100);

        if ($name !== '') {
            $data = [
                'name' => $name,
                'description' => $description,
                'estimated_days' => $estimatedDays,
                'rate_paise' => $ratePaise,
                'free_above_paise' => $freePaise,
                'min_subtotal_paise' => $minPaise,
                'sort_order' => $sortOrder,
                'active' => $active,
            ];
            if ($action === 'update' && !empty($_POST['id'])) {
                $data['id'] = trim($_POST['id']);
                Models::saveShippingOption($data);
                Helpers::setFlash('success', "Shipping option \"{$name}\" updated successfully!");
            } else {
                $data['id'] = 'ship_' . bin2hex(random_bytes(6));
                Models::saveShippingOption($data);
                Helpers::setFlash('success', "Shipping option \"{$name}\" created successfully!");
            }
        } else {
            Helpers::setFlash('error', "Method Name is required.");
        }
    } elseif ($action === 'toggle_active') {
        $id = trim($_POST['id'] ?? '');
        if ($id) {
            Models::toggleShippingOption($id);
            Helpers::setFlash('success', "Shipping option status updated!");
        }
    } elseif ($action === 'delete') {
        $id = trim($_POST['id'] ?? '');
        if ($id) {
            Models::deleteShippingOption($id);
            Helpers::setFlash('success', "Shipping option removed successfully.");
        }
    }

    Helpers::redirect(Helpers::url('admin/shipping'));
}

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');

$options = Models::getShippingOptions(false);

$filtered = array_filter($options, function ($o) use ($search, $statusFilter) {
    if ($search !== '') {
        $q = strtolower($search);
        $matchName = strpos(strtolower($o['name']), $q) !== false;
        $matchDesc = strpos(strtolower($o['description'] ?? ''), $q) !== false;
        if (!$matchName && !$matchDesc) return false;
    }
    if ($statusFilter === 'active' && !$o['active']) return false;
    if ($statusFilter === 'inactive' && $o['active']) return false;
    return true;
});

// KPI Metrics
$totalMethods = count($options);
$activeMethods = count(array_filter($options, fn($o) => !empty($o['active'])));
$freeRules = count(array_filter($options, fn($o) => !empty($o['free_above_paise'])));
$inactiveMethods = count(array_filter($options, fn($o) => empty($o['active'])));

$pageTitle = 'Shipping & Delivery Rates';
$activeNav = 'shipping';
require __DIR__ . '/../layouts/admin_layout.php';
?>

<div class="coupon-v3-page-wrap">
  <!-- Header Banner -->
  <div class="coupon-v3-header-wrap">
    <div class="coupon-v3-header-left">
      <div class="admin-v3-breadcrumbs">
        <a href="<?= Helpers::url('admin') ?>">Dashboard</a>
        <span aria-hidden="true">/</span>
        <span>Shipping Rates</span>
      </div>
      <h1 class="coupon-v3-title">
        Shipping &amp; <em>Delivery Rates</em>
      </h1>
      <p class="coupon-v3-subtitle">
        Configure customer shipping options, estimated delivery days, and free delivery conditions.
      </p>
    </div>

    <div class="admin-v3-header-right">
      <button
        type="button"
        onclick="openShippingForm('create')"
        class="admin-v3-btn-create-order"
      >
        <span aria-hidden="true" style="font-size: 16px; font-weight: 700;">+</span> Add Shipping Option
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

  <!-- KPI Metric Cards -->
  <div class="admin-v3-kpi-grid">
    <!-- Card 1: Total Options -->
    <div class="admin-v3-kpi-card">
      <div class="admin-v3-kpi-left">
        <div class="admin-v3-kpi-icon-box" style="background: #EAF5EC; color: #2E7D32;">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8">
            <rect x="1" y="3" width="15" height="13" />
            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8" />
            <circle cx="5.5" cy="18.5" r="2.5" />
            <circle cx="18.5" cy="18.5" r="2.5" />
          </svg>
        </div>
        <div class="admin-v3-kpi-info">
          <span class="admin-v3-kpi-num"><?= $totalMethods ?></span>
          <span class="admin-v3-kpi-label">TOTAL METHODS</span>
        </div>
      </div>
      <img src="<?= Helpers::asset('singlebadam.png') ?>" alt="" class="admin-kpi-thumb">
    </div>

    <!-- Card 2: Active Methods -->
    <div class="admin-v3-kpi-card">
      <div class="admin-v3-kpi-left">
        <div class="admin-v3-kpi-icon-box" style="background: #F7F1E5; color: #8E6D38;">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
            <polyline points="22 4 12 14.01 9 11.01" />
          </svg>
        </div>
        <div class="admin-v3-kpi-info">
          <span class="admin-v3-kpi-num"><?= $activeMethods ?></span>
          <span class="admin-v3-kpi-label">ACTIVE METHODS</span>
        </div>
      </div>
      <img src="<?= Helpers::asset('singlepistaproduct.png') ?>" alt="" class="admin-kpi-thumb">
    </div>

    <!-- Card 3: Free Shipping Rules -->
    <div class="admin-v3-kpi-card">
      <div class="admin-v3-kpi-left">
        <div class="admin-v3-kpi-icon-box" style="background: #FEF6E9; color: #B4691B;">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8">
            <line x1="12" y1="1" x2="12" y2="23" />
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
          </svg>
        </div>
        <div class="admin-v3-kpi-info">
          <span class="admin-v3-kpi-num"><?= $freeRules ?></span>
          <span class="admin-v3-kpi-label">FREE SHIPPING RULES</span>
        </div>
      </div>
      <img src="<?= Helpers::asset('mixed dry fruits.png') ?>" alt="" class="admin-kpi-thumb">
    </div>

    <!-- Card 4: Inactive Methods -->
    <div class="admin-v3-kpi-card">
      <div class="admin-v3-kpi-left">
        <div class="admin-v3-kpi-icon-box" style="background: #FDE8E8; color: #C53030;">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8">
            <circle cx="12" cy="12" r="10" />
            <line x1="15" y1="9" x2="9" y2="15" />
            <line x1="9" y1="9" x2="15" y2="15" />
          </svg>
        </div>
        <div class="admin-v3-kpi-info">
          <span class="admin-v3-kpi-num"><?= $inactiveMethods ?></span>
          <span class="admin-v3-kpi-label">INACTIVE / PAUSED</span>
        </div>
      </div>
      <img src="<?= Helpers::asset('dates-reference.png') ?>" alt="" class="admin-kpi-thumb">
    </div>
  </div>

  <!-- Inline Form Drawer / Card (Hidden by default, shown on click) -->
  <div id="inlineShippingForm" class="admin-card" style="display: none; margin-bottom: 24px; border: 2px solid #073D26; background: #FFFEFA;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ECE7DE; padding-bottom: 12px; margin-bottom: 16px;">
      <h2 id="shippingFormHeading" style="margin: 0; color: #073D26; font-size: 18px;">
        + Create New Shipping Option
      </h2>
      <button type="button" onclick="document.getElementById('inlineShippingForm').style.display='none'" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #888;">&times;</button>
    </div>

    <form method="POST" class="admin-form">
      <input type="hidden" id="form_action" name="action" value="create">
      <input type="hidden" id="form_id" name="id" value="">

      <div class="form-group-row">
        <div class="form-group" style="flex: 2;">
          <label for="form_name">Method Name *</label>
          <input
            id="form_name"
            name="name"
            required
            placeholder="e.g. Standard Surface Delivery"
          >
        </div>

        <div class="form-group" style="flex: 1;">
          <label for="form_days">Estimated Delivery (SLA)</label>
          <input
            id="form_days"
            name="estimated_days"
            placeholder="e.g. 3 – 5 Business Days"
          >
        </div>
      </div>

      <div class="form-group">
        <label for="form_desc">Description for Customers (shown at Checkout)</label>
        <textarea
          id="form_desc"
          name="description"
          rows="2"
          placeholder="e.g. Carefully packed in moisture-sealed canisters and dispatched via Bluedart / Delhivery."
        ></textarea>
      </div>

      <div class="form-group-row three-col" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px;">
        <div class="form-group">
          <label for="form_rate">Base Shipping Fee (₹) *</label>
          <input
            id="form_rate"
            type="number"
            min="0"
            step="1"
            name="rate_inr"
            required
            placeholder="49"
          >
        </div>

        <div class="form-group">
          <label for="form_free">Free Above Cart Value (₹, optional)</label>
          <input
            id="form_free"
            type="number"
            min="0"
            step="1"
            name="free_above_inr"
            placeholder="e.g. 999 (Orders >= ₹999 get free shipping)"
          >
        </div>

        <div class="form-group">
          <label for="form_min">Min Order Subtotal (₹ condition)</label>
          <input
            id="form_min"
            type="number"
            min="0"
            step="1"
            name="min_subtotal_inr"
            placeholder="0"
            value="0"
          >
        </div>

        <div class="form-group">
          <label for="form_sort">Display Sort Order</label>
          <input
            id="form_sort"
            type="number"
            min="0"
            name="sort_order"
            value="1"
          >
        </div>
      </div>

      <div class="form-checkbox-row" style="margin: 14px 0 18px;">
        <label class="checkbox-label" style="display: flex; gap: 8px; align-items: center; cursor: pointer;">
          <input id="form_active" type="checkbox" name="active" value="1" checked>
          <span><strong>Active &amp; Selectable by Customers at Checkout</strong></span>
        </label>
      </div>

      <div style="display: flex; gap: 10px;">
        <button type="submit" class="button button-primary" style="padding: 10px 24px;">
          Save Shipping Option
        </button>
        <button
          type="button"
          class="button button-outline"
          onclick="document.getElementById('inlineShippingForm').style.display='none'"
          style="background: #fff;"
        >
          Cancel
        </button>
      </div>
    </form>
  </div>

  <!-- Filter / Search Row -->
  <div class="admin-v3-filter-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 14px;">
    <div style="display: flex; gap: 10px; align-items: center;">
      <a href="<?= Helpers::url('admin/shipping') ?>" class="button <?= ($statusFilter === 'all') ? 'button-primary' : 'button-outline' ?>" style="font-size: 13px; padding: 6px 14px;">
        All (<?= $totalMethods ?>)
      </a>
      <a href="<?= Helpers::url('admin/shipping?status=active') ?>" class="button <?= ($statusFilter === 'active') ? 'button-primary' : 'button-outline' ?>" style="font-size: 13px; padding: 6px 14px;">
        Active (<?= $activeMethods ?>)
      </a>
      <a href="<?= Helpers::url('admin/shipping?status=inactive') ?>" class="button <?= ($statusFilter === 'inactive') ? 'button-primary' : 'button-outline' ?>" style="font-size: 13px; padding: 6px 14px;">
        Inactive (<?= $inactiveMethods ?>)
      </a>
    </div>

    <form method="GET" style="display: flex; gap: 8px;">
      <?php if ($statusFilter !== 'all'): ?>
        <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
      <?php endif; ?>
      <input
        type="search"
        name="search"
        value="<?= htmlspecialchars($search) ?>"
        placeholder="Search methods..."
        style="padding: 7px 12px; border: 1px solid #D5CEBF; border-radius: 6px; font-size: 13px; background: #fff;"
      >
      <button type="submit" class="button button-outline" style="background:#fff; font-size:13px; padding: 7px 14px;">
        Search
      </button>
    </form>
  </div>

  <!-- Shipping Options Table -->
  <div class="admin-card" style="padding: 0; overflow: hidden;">
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Method Name &amp; SLA</th>
            <th>Description</th>
            <th>Base Rate</th>
            <th>Condition / Free Threshold</th>
            <th>Min Subtotal</th>
            <th>Status</th>
            <th style="text-align: right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($filtered)): ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 36px; color: #888;">
                No shipping methods match the selected filter.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($filtered as $o): 
              $rateInr = round($o['rate_paise'] / 100);
              $freeInr = $o['free_above_paise'] ? round($o['free_above_paise'] / 100) : null;
              $minInr = round($o['min_subtotal_paise'] / 100);
            ?>
              <tr>
                <td>
                  <strong style="color: #073D26; font-size: 15px;"><?= htmlspecialchars($o['name']) ?></strong>
                  <?php if (!empty($o['estimated_days'])): ?>
                    <div style="margin-top: 3px;">
                      <span class="badge badge-neutral" style="font-size: 11px; background: #F4EFE6; color: #5D4F3C;">
                        ⏱ <?= htmlspecialchars($o['estimated_days']) ?>
                      </span>
                    </div>
                  <?php endif; ?>
                </td>
                <td style="max-width: 280px; font-size: 13px; color: #666; line-height: 1.4;">
                  <?= htmlspecialchars($o['description'] ?: '—') ?>
                </td>
                <td>
                  <strong style="font-size: 15px;"><?= Helpers::formatPaise($o['rate_paise']) ?></strong>
                </td>
                <td>
                  <?php if ($freeInr !== null): ?>
                    <span style="color: #2E7D32; font-weight: 600; font-size: 13px;">
                      FREE on orders &ge; ₹<?= $freeInr ?>
                    </span>
                  <?php else: ?>
                    <span style="color: #888; font-size: 13px;">Always charged</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($minInr > 0): ?>
                    <span style="font-size: 13px; color: #4A4031;">&ge; ₹<?= $minInr ?></span>
                  <?php else: ?>
                    <span style="font-size: 13px; color: #888;">None (₹0)</span>
                  <?php endif; ?>
                </td>
                <td>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="id" value="<?= $o['id'] ?>">
                    <button
                      type="submit"
                      class="badge-toggle <?= !empty($o['active']) ? 'badge-success' : 'badge-danger' ?>"
                      style="cursor: pointer; border: none;"
                      title="Click to toggle active status"
                    >
                      <?= !empty($o['active']) ? 'Active' : 'Paused' ?>
                    </button>
                  </form>
                </td>
                <td style="text-align: right;">
                  <div style="display: inline-flex; gap: 8px;">
                    <button
                      type="button"
                      class="btn-action"
                      onclick='editShippingOption(<?= json_encode($o) ?>)'
                      style="padding: 5px 10px; font-size: 12px; background: #FAF7F2; border: 1px solid #DFD7CA; border-radius: 4px; cursor: pointer;"
                    >
                      ✏️ Edit
                    </button>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete shipping method \'<?= htmlspecialchars(addslashes($o['name'])) ?>\'?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= $o['id'] ?>">
                      <button
                        type="submit"
                        class="admin-remove-btn"
                        style="padding: 5px 10px; font-size: 12px;"
                      >
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
  </div>
</div>

<script>
  function openShippingForm(mode) {
    const formBox = document.getElementById('inlineShippingForm');
    formBox.style.display = 'block';
    if (mode === 'create') {
      document.getElementById('shippingFormHeading').textContent = '+ Create New Shipping Option';
      document.getElementById('form_action').value = 'create';
      document.getElementById('form_id').value = '';
      document.getElementById('form_name').value = '';
      document.getElementById('form_days').value = '3 – 5 Business Days';
      document.getElementById('form_desc').value = '';
      document.getElementById('form_rate').value = '49';
      document.getElementById('form_free').value = '999';
      document.getElementById('form_min').value = '0';
      document.getElementById('form_sort').value = '1';
      document.getElementById('form_active').checked = true;
      formBox.scrollIntoView({ behavior: 'smooth' });
    }
  }

  function editShippingOption(opt) {
    const formBox = document.getElementById('inlineShippingForm');
    formBox.style.display = 'block';
    document.getElementById('shippingFormHeading').textContent = '✏️ Edit: ' + opt.name;
    document.getElementById('form_action').value = 'update';
    document.getElementById('form_id').value = opt.id;
    document.getElementById('form_name').value = opt.name || '';
    document.getElementById('form_days').value = opt.estimated_days || '';
    document.getElementById('form_desc').value = opt.description || '';
    document.getElementById('form_rate').value = Math.round(opt.rate_paise / 100);
    document.getElementById('form_free').value = opt.free_above_paise ? Math.round(opt.free_above_paise / 100) : '';
    document.getElementById('form_min').value = opt.min_subtotal_paise ? Math.round(opt.min_subtotal_paise / 100) : '0';
    document.getElementById('form_sort').value = opt.sort_order || 1;
    document.getElementById('form_active').checked = (opt.active == 1);
    formBox.scrollIntoView({ behavior: 'smooth' });
  }
</script>

<?php require __DIR__ . '/../layouts/admin_layout_bottom.php'; ?>
