<?php
/**
 * SVG Dry Fruits - Admin Dashboard Overview
 * 1:1 match of Node.js src/app/admin/page.tsx
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Icon.php';
require_once ROOT_PATH . '/src/Models.php';
require_once ROOT_PATH . '/src/Auth.php';

Auth::requireAuth();

$pdo = Database::getConnection();

// Handle manual Cashfree sync action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sync_cashfree') {
    Helpers::requireCsrf();
    $pendingOrders = $pdo->query("SELECT order_number FROM orders WHERE payment_status = 'pending' ORDER BY created_at DESC LIMIT 25")->fetchAll(PDO::FETCH_COLUMN);
    $synced = 0;
    if (!empty($pendingOrders)) {
        require_once ROOT_PATH . '/config/cashfree.php';
        $cashfree = new CashfreePG();
        foreach ($pendingOrders as $ordNum) {
            try {
                $cfOrder = $cashfree->getOrder($ordNum);
                if (strtoupper($cfOrder['order_status'] ?? '') === 'PAID') {
                    Models::updateOrderPayment($ordNum, 'paid', $cfOrder['cf_order_id'] ?? null);
                    $synced++;
                }
            } catch (Throwable $e) {}
        }
    }
    Helpers::setFlash('success', "Cashfree sync complete! {$synced} order(s) updated to PAID.");
    Helpers::redirect(Helpers::url('admin'));
}

// Auto-sync recent pending orders with Cashfree (throttled to once every 10 minutes)
$lastAutoSync = (int)($_SESSION['last_dashboard_cf_sync'] ?? 0);
if (time() - $lastAutoSync > 600) {
    $_SESSION['last_dashboard_cf_sync'] = time();
    try {
        $cutoff = date('Y-m-d H:i:s', time() - 86400);
        $pendingStmt = $pdo->prepare("SELECT order_number FROM orders WHERE payment_status = 'pending' AND created_at >= ? LIMIT 10");
        $pendingStmt->execute([$cutoff]);
        $recentPending = $pendingStmt->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($recentPending)) {
            require_once ROOT_PATH . '/config/cashfree.php';
            $cashfree = new CashfreePG();
            foreach ($recentPending as $ordNum) {
                try {
                    $cfOrder = $cashfree->getOrder($ordNum);
                    if (strtoupper($cfOrder['order_status'] ?? '') === 'PAID') {
                        Models::updateOrderPayment($ordNum, 'paid', $cfOrder['cf_order_id'] ?? null);
                    }
                } catch (Throwable $e) {}
            }
        }
    } catch (Throwable $e) {}
}

// 1. Total orders count
$totalOrdersCount = (int)$pdo->query("SELECT count(*) FROM orders")->fetchColumn();

// 2. Verified revenue (paid orders only)
$verifiedRevenuePaise = (int)$pdo->query("SELECT coalesce(sum(total_paise), 0) FROM orders WHERE payment_status = 'paid'")->fetchColumn();

// 3. Orders awaiting fulfillment
$awaitingActionCount = (int)$pdo->query("
    SELECT count(*) FROM orders 
    WHERE payment_status = 'paid' 
      AND fulfillment_status IN ('confirmed', 'processing', 'packed', 'awaiting_payment', 'pending')
")->fetchColumn();

// 4. Low stock variants
$lowStockRows = $pdo->query("
    SELECT v.id as variant_id, v.sku, v.label, v.stock, v.low_stock_threshold as threshold, p.name as product_name, p.slug as product_slug
    FROM variants v
    INNER JOIN products p ON p.id = v.product_id
    WHERE v.active = 1 AND v.stock <= v.low_stock_threshold
    LIMIT 10
")->fetchAll();

// 5. Recent orders
$recentOrders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 8")->fetchAll();

$pageTitle = 'Dashboard Overview';
$activeNav = 'dashboard';
require __DIR__ . '/../layouts/admin_layout.php';
?>

<div class="admin-content">
  <div class="admin-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
      <h1>Dashboard <em>Overview</em></h1>
      <p>Real-time metrics and order activity for SVG Dry Fruits.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
      <form method="POST" style="display: inline;">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="action" value="sync_cashfree">
        <button type="submit" class="button button-outline" style="background: #fff; color: #073D26; border-color: #d4cabc; font-weight: 600;">
          🔄 Sync Cashfree Status
        </button>
      </form>
      <a href="<?= Helpers::url('admin/products') ?>" class="button button-primary">
        Products &amp; Stock →
      </a>
      <a href="<?= Helpers::url('admin/products/new') ?>" class="button button-secondary">
        + Add Product
      </a>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="admin-kpi-grid">
    <div class="admin-kpi-card">
      <div class="kpi-icon-wrap"><?= Icon::render('card', 24) ?></div>
      <div>
        <span class="kpi-label">Verified Revenue</span>
        <strong class="kpi-value"><?= Helpers::formatPaise($verifiedRevenuePaise) ?></strong>
        <span class="kpi-hint">Paid orders only</span>
      </div>
    </div>

    <div class="admin-kpi-card">
      <div class="kpi-icon-wrap"><?= Icon::render('bag', 24) ?></div>
      <div>
        <span class="kpi-label">Total Orders</span>
        <strong class="kpi-value"><?= number_format($totalOrdersCount) ?></strong>
        <span class="kpi-hint">All historical checkouts</span>
      </div>
    </div>

    <div class="admin-kpi-card">
      <div class="kpi-icon-wrap"><?= Icon::render('truck', 24) ?></div>
      <div>
        <span class="kpi-label">Awaiting Fulfillment</span>
        <strong class="kpi-value"><?= number_format($awaitingActionCount) ?></strong>
        <span class="kpi-hint">Confirmed &amp; packing</span>
      </div>
    </div>

    <div class="admin-kpi-card">
      <div class="kpi-icon-wrap"><?= Icon::render('scale', 24) ?></div>
      <div>
        <span class="kpi-label">Low Stock Alerts</span>
        <strong class="kpi-value"><?= count($lowStockRows) ?></strong>
        <span class="kpi-hint">Variants at or below threshold</span>
      </div>
    </div>
  </div>

  <div class="admin-two-cols">
    <!-- Recent Orders Table -->
    <div class="admin-card">
      <div class="admin-card-head">
        <h2>Recent Orders</h2>
        <a href="<?= Helpers::url('admin/orders') ?>" class="underlined-link">View all orders →</a>
      </div>

      <?php if (empty($recentOrders)): ?>
        <p class="admin-empty-text">No orders placed yet. Orders will appear here in real time.</p>
      <?php else: ?>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Fulfillment</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentOrders as $o): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($o['order_number']) ?></strong></td>
                  <td><?= htmlspecialchars($o['customer_name']) ?></td>
                  <td><?= Helpers::formatPaise((int)$o['total_paise']) ?></td>
                  <td>
                    <span class="badge <?= $o['payment_status'] === 'paid' ? 'badge-success' : ($o['payment_status'] === 'pending' ? 'badge-warning' : 'badge-danger') ?>">
                      <?= htmlspecialchars($o['payment_status']) ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge badge-neutral"><?= htmlspecialchars(str_replace('_', ' ', $o['fulfillment_status'])) ?></span>
                  </td>
                  <td>
                    <a href="<?= Helpers::url('admin/orders/detail?id=' . $o['id']) ?>" class="admin-action-link">
                      Manage →
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Low Stock Warning Box -->
    <div class="admin-card">
      <div class="admin-card-head">
        <h2>Low Stock Alerts</h2>
        <a href="<?= Helpers::url('admin/products') ?>" class="underlined-link">Manage inventory →</a>
      </div>

      <?php if (empty($lowStockRows)): ?>
        <p class="admin-empty-text">All active variants are above their low-stock thresholds.</p>
      <?php else: ?>
        <ul class="admin-alert-list">
          <?php foreach ($lowStockRows as $r): ?>
            <li class="admin-alert-item">
              <div>
                <strong><?= htmlspecialchars($r['product_name']) ?> (<?= htmlspecialchars($r['label']) ?>)</strong>
                <span>SKU: <?= htmlspecialchars($r['sku']) ?></span>
              </div>
              <div class="admin-stock-badge">
                <strong><?= $r['stock'] ?> left</strong>
                <span>Threshold: <?= $r['threshold'] ?></span>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../layouts/admin_layout_bottom.php'; ?>
