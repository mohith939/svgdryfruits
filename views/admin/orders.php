<?php
/**
 * SVG Dry Fruits - Orders & Fulfilment Management
 * 1:1 match of Node.js src/app/admin/orders/page.tsx
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Models.php';
require_once ROOT_PATH . '/src/Auth.php';

Auth::requireAuth();

$pdo = Database::getConnection();

// Handle status updates or manual order creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Helpers::requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $orderId = trim($_POST['order_id'] ?? '');
        $status = trim($_POST['status'] ?? '');
        if ($orderId && $status) {
            $stmt = $pdo->prepare("UPDATE orders SET fulfillment_status = ? WHERE id = ?");
            $stmt->execute([$status, $orderId]);
            if ($status === 'cancelled') {
                Models::restoreOrderStock($orderId);
            }
            Helpers::setFlash('success', "Order status updated to \"{$status}\".");
        }
    } elseif ($action === 'create_order') {
        $custName = trim($_POST['customer_name'] ?? '');
        $custPhone = trim($_POST['customer_phone'] ?? '');
        $custEmail = trim($_POST['customer_email'] ?? '') ?: null;
        $street = trim($_POST['street'] ?? 'Indiranagar');
        $city = trim($_POST['city'] ?? 'Bengaluru');
        $state = trim($_POST['state'] ?? 'Karnataka');
        $pincode = trim($_POST['pincode'] ?? '560038');
        $productName = trim($_POST['product_name'] ?? 'California Almonds');
        $variantLabel = trim($_POST['variant_label'] ?? '500 g');
        $unitPriceRupees = (float)($_POST['unit_price'] ?? 679);
        $qty = max(1, (int)($_POST['quantity'] ?? 1));
        $payStatus = trim($_POST['payment_status'] ?? 'paid');
        $fulStatus = trim($_POST['fulfillment_status'] ?? 'pending');

        if ($custName && $custPhone) {
            $orderId = 'ord_' . bin2hex(random_bytes(8));
            $orderNumber = 'SVG' . date('ymd') . strtoupper(bin2hex(random_bytes(2)));
            $trackingToken = bin2hex(random_bytes(16));
            $unitPricePaise = (int)round($unitPriceRupees * 100);
            $totalPaise = $unitPricePaise * $qty;

            $addrJson = json_encode([
                'street' => $street,
                'city' => $city,
                'state' => $state,
                'pincode' => $pincode,
                'line1' => $street,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $stmt = $pdo->prepare("
                INSERT INTO orders (id, order_number, tracking_token, payment_status, fulfillment_status, customer_name, customer_email, customer_phone, shipping_address, subtotal_paise, discount_paise, shipping_paise, total_paise)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?)
            ");
            $stmt->execute([
                $orderId, $orderNumber, $trackingToken, $payStatus, $fulStatus,
                $custName, $custEmail, $custPhone, $addrJson, $totalPaise, $totalPaise
            ]);

            $itemStmt = $pdo->prepare("
                INSERT INTO order_items (id, order_id, product_name, variant_label, unit_price_paise, quantity)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $itemStmt->execute(['item_' . bin2hex(random_bytes(8)), $orderId, $productName, $variantLabel, $unitPricePaise, $qty]);

            Helpers::setFlash('success', "Order #{$orderNumber} created successfully!");
        } else {
            Helpers::setFlash('error', "Customer Name and Mobile Number are required.");
        }
    } elseif ($action === 'sync_cashfree') {
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
    }

    Helpers::redirect(Helpers::url('admin/orders'));
}

// Auto-sync recent pending orders with Cashfree (last 24 hours)
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

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');
$dateFilter = trim($_GET['date'] ?? 'last30');

// Fetch all orders
$orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();

function normalizeStatus(string $status): string {
    if (in_array($status, ['awaiting_payment', 'pending', 'confirmed'])) return 'pending';
    if (in_array($status, ['processing', 'packed'])) return 'processing';
    if ($status === 'shipped') return 'shipped';
    if ($status === 'delivered') return 'delivered';
    if ($status === 'cancelled') return 'cancelled';
    return 'pending';
}

// In-memory filter matching Node.js
$filtered = array_filter($orders, function ($o) use ($search, $statusFilter, $dateFilter) {
    if ($search !== '') {
        $q = strtolower($search);
        $numMatch = strpos(strtolower($o['order_number']), $q) !== false;
        $nameMatch = strpos(strtolower($o['customer_name'] ?? ''), $q) !== false;
        $phoneMatch = strpos($o['customer_phone'] ?? '', $q) !== false;
        if (!$numMatch && !$nameMatch && !$phoneMatch) return false;
    }

    $norm = normalizeStatus($o['fulfillment_status']);
    if ($statusFilter !== 'all' && $norm !== $statusFilter) {
        return false;
    }

    if ($dateFilter !== 'all') {
        $orderTime = strtotime($o['created_at']);
        $diffDays = (time() - $orderTime) / (3600 * 24);
        if ($dateFilter === 'today' && $diffDays > 1) return false;
        if ($dateFilter === 'last7' && $diffDays > 7) return false;
        if ($dateFilter === 'last30' && $diffDays > 30) return false;
    }

    return true;
});

// KPI Metrics
$totalOrdersCount = count($orders);
$pendingCount = 0;
$processingCount = 0;
$totalRevenuePaise = 0;
foreach ($orders as $o) {
    $norm = normalizeStatus($o['fulfillment_status']);
    if ($norm === 'pending') $pendingCount++;
    if ($norm === 'processing') $processingCount++;
    if ($o['payment_status'] === 'paid') {
        $totalRevenuePaise += (int)$o['total_paise'];
    }
}

// Avatar Colors
$avatarColors = ['avatar-orange', 'avatar-green', 'avatar-pink', 'avatar-blue', 'avatar-purple', 'avatar-teal'];
function getAvatarClass(string $name, array $avatarColors): string {
    return $avatarColors[abs(crc32($name)) % count($avatarColors)];
}
function getInitials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2)) ?: 'C';
}

$pageTitle = 'Orders & Fulfilment';
$activeNav = 'orders';
require __DIR__ . '/../layouts/admin_layout.php';
?>

<div class="coupon-v3-page-wrap">
  <!-- Header Banner matching Node.js -->
  <div class="coupon-v3-header-wrap">
    <div class="coupon-v3-header-left">
      <div class="admin-v3-breadcrumbs">
        <a href="<?= Helpers::url('admin') ?>">Dashboard</a>
        <span aria-hidden="true">/</span>
        <span>Orders</span>
      </div>
      <h1 class="coupon-v3-title">
        Orders &amp; <em>Fulfilment</em>
      </h1>
      <p class="coupon-v3-subtitle">
        Track purchases, manage fulfilment, and keep every order moving.
      </p>
    </div>

    <div class="admin-v3-header-right" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
      <form method="POST" style="display: inline;">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="action" value="sync_cashfree">
        <button type="submit" class="button button-outline" style="background: #fff; color: #073D26; border-color: #d4cabc; font-weight: 600; padding: 10px 14px; font-size: 13px;" title="Query Cashfree API and auto-update pending orders">
          🔄 Sync Cashfree Payments
        </button>
      </form>
      <button
        type="button"
        onclick="document.getElementById('createOrderModal').style.display='flex'"
        class="admin-v3-btn-create-order"
      >
        <span aria-hidden="true" style="font-size: 16px; font-weight: 700;">+</span> Create Order
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
    <!-- Card 1: Total Orders -->
    <div class="admin-v3-kpi-card">
      <div class="admin-v3-kpi-left">
        <div class="admin-v3-kpi-icon-box" style="background: #EAF5EC; color: #2E7D32;">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="21" r="1" />
            <circle cx="20" cy="21" r="1" />
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
          </svg>
        </div>
        <div class="admin-v3-kpi-info">
          <span class="admin-v3-kpi-num"><?= $totalOrdersCount ?></span>
          <span class="admin-v3-kpi-label">TOTAL ORDERS</span>
        </div>
      </div>
      <img src="<?= Helpers::asset('singlebadam.png') ?>" alt="" class="admin-kpi-thumb">
    </div>

    <!-- Card 2: Pending Orders -->
    <div class="admin-v3-kpi-card">
      <div class="admin-v3-kpi-left">
        <div class="admin-v3-kpi-icon-box" style="background: #F7EFE4; color: #8E6D38;">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 22h14" />
            <path d="M5 2h14" />
            <path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22" />
            <path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2" />
          </svg>
        </div>
        <div class="admin-v3-kpi-info">
          <span class="admin-v3-kpi-num"><?= $pendingCount < 10 ? '0' . $pendingCount : $pendingCount ?></span>
          <span class="admin-v3-kpi-label">PENDING ORDERS</span>
        </div>
      </div>
      <img src="<?= Helpers::asset('acrod image.png') ?>" alt="" class="admin-kpi-thumb">
    </div>

    <!-- Card 3: Processing Orders -->
    <div class="admin-v3-kpi-card">
      <div class="admin-v3-kpi-left">
        <div class="admin-v3-kpi-icon-box" style="background: #E9F2FA; color: #1D4ED8;">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="3" />
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
          </svg>
        </div>
        <div class="admin-v3-kpi-info">
          <span class="admin-v3-kpi-num"><?= $processingCount < 10 ? '0' . $processingCount : $processingCount ?></span>
          <span class="admin-v3-kpi-label">PROCESSING ORDERS</span>
        </div>
      </div>
      <img src="<?= Helpers::asset('singlepistaproduct.png') ?>" alt="" class="admin-kpi-thumb">
    </div>

    <!-- Card 4: Total Revenue -->
    <div class="admin-v3-kpi-card">
      <div class="admin-v3-kpi-left">
        <div class="admin-v3-kpi-icon-box" style="background: #EDF7EE; color: #15803D;">
          <span style="font-size: 18px; font-weight: 700;">₹</span>
        </div>
        <div class="admin-v3-kpi-info">
          <span class="admin-v3-kpi-num"><?= Helpers::formatPaise($totalRevenuePaise) ?></span>
          <span class="admin-v3-kpi-label">TOTAL REVENUE</span>
        </div>
      </div>
      <img src="<?= Helpers::asset('drydate image.png') ?>" alt="" class="admin-kpi-thumb">
    </div>
  </div>

  <!-- Search & Filter Toolbar -->
  <form class="admin-v3-toolbar" method="GET" action="<?= Helpers::url('admin/orders') ?>">
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
        placeholder="Search by order ID, customer, or phone..."
        class="admin-v3-search-input"
      >
    </div>

    <select name="status" class="admin-v3-cat-select">
      <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
      <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
      <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>Processing</option>
      <option value="shipped" <?= $statusFilter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
      <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
      <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
    </select>

    <div class="coupon-v3-date-select-wrap">
      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="coupon-v3-date-icon">
        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
        <line x1="16" y1="2" x2="16" y2="6" />
        <line x1="8" y1="2" x2="8" y2="6" />
        <line x1="3" y1="10" x2="21" y2="10" />
      </svg>
      <select name="date" class="coupon-v3-date-select">
        <option value="last30" <?= $dateFilter === 'last30' ? 'selected' : '' ?>>Last 30 days</option>
        <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Today</option>
        <option value="last7" <?= $dateFilter === 'last7' ? 'selected' : '' ?>>Last 7 days</option>
        <option value="all" <?= $dateFilter === 'all' ? 'selected' : '' ?>>All Time</option>
      </select>
    </div>

    <button type="submit" class="admin-v3-filter-btn">
      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
      </svg>
      Filter
    </button>
  </form>

  <!-- Orders Data Table -->
  <div class="admin-v3-table-card">
    <div class="admin-v3-table-wrap">
      <table class="admin-v3-table">
        <thead>
          <tr>
            <th style="width: 44px;">
              <input type="checkbox" class="admin-v3-checkbox" aria-label="Select all">
            </th>
            <th style="min-width: 110px;">ORDER</th>
            <th style="min-width: 170px;">CUSTOMER</th>
            <th style="min-width: 160px;">DATE</th>
            <th style="min-width: 100px;">ITEMS</th>
            <th style="min-width: 110px;">TOTAL</th>
            <th style="min-width: 120px;">PAYMENT</th>
            <th style="min-width: 140px;">ORDER STATUS</th>
            <th style="width: 80px; text-align: center;">ACTIONS</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($filtered)): ?>
            <tr>
              <td colspan="9" style="text-align: center; padding: 40px 0; color: #8C8476;">
                No matching orders found.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($filtered as $o): 
              $norm = normalizeStatus($o['fulfillment_status']);
              $isPaid = ($o['payment_status'] === 'paid');
              $avatarClass = getAvatarClass($o['customer_name'], $avatarColors);
              $initials = getInitials($o['customer_name']);
              $dateFormatted = date('d M Y, h:i A', strtotime($o['created_at']));
            ?>
              <tr>
                <td>
                  <input type="checkbox" class="admin-v3-checkbox" aria-label="Select order <?= htmlspecialchars($o['order_number']) ?>">
                </td>

                <!-- Order Number -->
                <td>
                  <a href="<?= Helpers::url('admin/orders/detail?id=' . $o['id']) ?>" class="order-v3-link" style="text-decoration:none;">
                    #<?= htmlspecialchars(ltrim($o['order_number'], '#')) ?>
                  </a>
                </td>

                <!-- Customer with Avatar -->
                <td>
                  <div class="order-v3-cust-cell">
                    <div class="order-v3-avatar <?= $avatarClass ?>">
                      <?= $initials ?>
                    </div>
                    <span style="font-weight: 500; color: #1E2520;">
                      <?= htmlspecialchars($o['customer_name']) ?>
                    </span>
                  </div>
                </td>

                <!-- Date -->
                <td style="color: #5C5549; font-size: 13px;">
                  <?= $dateFormatted ?>
                </td>

                <!-- Items -->
                <td style="color: #374136; font-weight: 500; font-size: 13.5px;">
                  1 item
                </td>

                <!-- Total -->
                <td style="font-weight: 700; color: #1E2520; font-size: 14px;">
                  <?= Helpers::formatPaise((int)$o['total_paise']) ?>
                </td>

                <!-- Payment Status Pill -->
                <td>
                  <span class="order-v3-pill <?= $isPaid ? 'pill-payment-paid' : 'pill-payment-pending' ?>">
                    <span class="order-v3-pill-dot"></span>
                    <?= $isPaid ? 'Paid' : 'Pending Payment' ?>
                  </span>
                </td>

                <!-- Order Status Pill -->
                <td>
                  <span class="order-v3-pill <?= 'pill-status-' . $norm ?>">
                    <span class="order-v3-pill-dot"></span>
                    <?= ucfirst($norm) ?>
                  </span>
                </td>

                <!-- Actions Dropdown -->
                <td style="text-align: center;">
                  <div style="position: relative; display: inline-block;">
                    <a
                      href="<?= Helpers::url('admin/orders/detail?id=' . $o['id']) ?>"
                      class="order-v3-actions-btn"
                      style="text-decoration:none;"
                      title="Manage Order"
                    >
                      •••
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination Footer -->
    <div class="order-v3-pagination">
      <div>
        Showing 1–<?= count($filtered) ?> of <?= $totalOrdersCount ?> orders
      </div>
      <div class="order-v3-pages">
        <button type="button" class="order-v3-page-num" disabled>&lt;</button>
        <button type="button" class="order-v3-page-num active">1</button>
        <button type="button" class="order-v3-page-num" disabled>&gt;</button>
      </div>
    </div>
  </div>
</div>

<!-- Create Order Modal -->
<div id="createOrderModal" class="coupon-v3-modal-backdrop" style="display: none;" onclick="if(event.target===this) this.style.display='none'">
  <div class="coupon-v3-modal-card" style="max-width: 620px;">
    <div class="coupon-v3-modal-header">
      <h2 class="coupon-v3-modal-title">Create New Order Manually</h2>
      <button type="button" onclick="document.getElementById('createOrderModal').style.display='none'" class="coupon-v3-modal-close">✕</button>
    </div>
    <form method="POST">
      <?= Helpers::csrfField() ?>
      <input type="hidden" name="action" value="create_order">
      <div style="display: flex; flex-direction: column; gap: 14px; padding: 20px 0;">
        <div class="coupon-v3-modal-grid">
          <div class="coupon-v3-field">
            <label class="coupon-v3-label">Customer Name *</label>
            <input type="text" name="customer_name" required placeholder="Mohith Chalamcharla" class="coupon-v3-input">
          </div>
          <div class="coupon-v3-field">
            <label class="coupon-v3-label">Customer Mobile *</label>
            <input type="text" name="customer_phone" required placeholder="9876543210" class="coupon-v3-input">
          </div>
        </div>

        <div class="coupon-v3-modal-grid">
          <div class="coupon-v3-field">
            <label class="coupon-v3-label">Product Name</label>
            <input type="text" name="product_name" value="California Almonds" class="coupon-v3-input">
          </div>
          <div class="coupon-v3-field">
            <label class="coupon-v3-label">Pack Size</label>
            <input type="text" name="variant_label" value="500 g" class="coupon-v3-input">
          </div>
        </div>

        <div class="coupon-v3-modal-grid">
          <div class="coupon-v3-field">
            <label class="coupon-v3-label">Unit Price (₹)</label>
            <input type="number" name="unit_price" value="679" class="coupon-v3-input">
          </div>
          <div class="coupon-v3-field">
            <label class="coupon-v3-label">Quantity</label>
            <input type="number" name="quantity" value="1" min="1" class="coupon-v3-input">
          </div>
        </div>

        <div class="coupon-v3-field">
          <label class="coupon-v3-label">Street Address</label>
          <input type="text" name="street" value="Indiranagar, 100ft Road" class="coupon-v3-input">
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
          <button type="button" onclick="document.getElementById('createOrderModal').style.display='none'" class="coupon-v3-modal-cancel">Cancel</button>
          <button type="submit" class="coupon-v3-modal-submit">Create Order</button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../layouts/admin_layout_bottom.php'; ?>
