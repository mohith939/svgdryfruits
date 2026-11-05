<?php
/**
 * SVG Dry Fruits - Order Detail & Fulfillment
 * 1:1 match of Node.js src/app/admin/orders/[id]/page.tsx
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Models.php';

$pdo = Database::getConnection();

$id = trim($_GET['id'] ?? '');

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    Helpers::setFlash('error', 'Order not found.');
    Helpers::redirect(Helpers::url('admin/orders'));
}

// Handle fulfillment status update or refund recording
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_fulfillment') {
        $fulfillmentStatus = trim($_POST['fulfillment_status'] ?? '');
        $courier = trim($_POST['courier'] ?? 'Shadowfax Express');
        $trackingNumber = trim($_POST['tracking_number'] ?? '');

        $stmt = $pdo->prepare("
            UPDATE orders 
            SET fulfillment_status = ?, courier = ?, tracking_number = ? 
            WHERE id = ?
        ");
        $stmt->execute([$fulfillmentStatus, $courier ?: null, $trackingNumber ?: null, $id]);
        Helpers::setFlash('success', 'Order fulfillment status & tracking updated successfully.');
    } elseif ($action === 'dispatch_shadowfax') {
        $awb = 'SFX' . date('ymd') . rand(1000, 9999);
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET fulfillment_status = 'shipped', courier = 'Shadowfax Express', tracking_number = ? 
            WHERE id = ?
        ");
        $stmt->execute([$awb, $id]);
        Helpers::setFlash('success', "✓ Dispatched via Shadowfax! AWB: {$awb}");
    } elseif ($action === 'record_refund') {
        $refundRupees = (float)($_POST['refund_amount'] ?? 0);
        $note = trim($_POST['refund_note'] ?? '');
        if ($refundRupees > 0 && $note) {
            $amountPaise = (int)round($refundRupees * 100);
            $refId = 'ref_' . bin2hex(random_bytes(8));
            $pdo->prepare("INSERT INTO refunds (id, order_id, amount_paise, note) VALUES (?, ?, ?, ?)")
                ->execute([$refId, $id, $amountPaise, $note]);

            if (isset($_POST['mark_refunded'])) {
                $pdo->prepare("UPDATE orders SET payment_status = 'refunded' WHERE id = ?")->execute([$id]);
            }
            Helpers::setFlash('success', 'Manual refund recorded and saved.');
        } else {
            Helpers::setFlash('error', 'Please enter a valid refund amount and reference note.');
        }
    } elseif ($action === 'sync_cashfree') {
        try {
            require_once ROOT_PATH . '/config/cashfree.php';
            $cashfree = new CashfreePG();
            $cfOrder = $cashfree->getOrder($order['order_number']);
            $cfStatus = strtoupper($cfOrder['order_status'] ?? '');
            if ($cfStatus === 'PAID') {
                Models::updateOrderPayment($order['order_number'], 'paid', $cfOrder['cf_order_id'] ?? null);
                Helpers::setFlash('success', '✓ Payment verified from Cashfree! Marked as PAID.');
            } else {
                Helpers::setFlash('warning', "Cashfree gateway reports order status as: {$cfStatus}");
            }
        } catch (Throwable $e) {
            Helpers::setFlash('error', 'Cashfree query error: ' . $e->getMessage());
        }
    }

    Helpers::redirect(Helpers::url('admin/orders/detail?id=' . $id));
}

// Auto-check Cashfree status if payment is currently pending
if ($order['payment_status'] !== 'paid') {
    try {
        require_once ROOT_PATH . '/config/cashfree.php';
        $cashfree = new CashfreePG();
        $cfOrder = $cashfree->getOrder($order['order_number']);
        $cfStatus = strtoupper($cfOrder['order_status'] ?? '');
        if ($cfStatus === 'PAID') {
            Models::updateOrderPayment($order['order_number'], 'paid', $cfOrder['cf_order_id'] ?? null);
            $order['payment_status'] = 'paid';
            $order['fulfillment_status'] = 'processing';
            $order['cashfree_order_id'] = $cfOrder['cf_order_id'] ?? ($order['cashfree_order_id'] ?? null);
            Helpers::setFlash('success', '✓ Payment auto-verified as PAID from Cashfree!');
        }
    } catch (Throwable $e) {}
}

// Order Items
$itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemStmt->execute([$id]);
$items = $itemStmt->fetchAll();

// Shipping Address
$addr = json_decode($order['shipping_address'] ?? '{}', true) ?: [];

// Refunds (wrapped in try-catch to prevent fatal crash if table is initializing)
$refunds = [];
try {
    $refStmt = $pdo->prepare("SELECT * FROM refunds WHERE order_id = ? ORDER BY created_at DESC");
    $refStmt->execute([$id]);
    $refunds = $refStmt->fetchAll() ?: [];
} catch (Throwable $e) {
    $refunds = [];
}

$isShadowfax = stripos($order['courier'] ?? '', 'shadowfax') !== false;

$pageTitle = 'Order ' . ($order['order_number'] ?? '');
$activeNav = 'orders';
require __DIR__ . '/../layouts/admin_layout.php';
?>

<div class="admin-content">
  <div class="admin-page-header">
    <div>
      <div class="breadcrumbs">
        <a href="<?= Helpers::url('admin') ?>">Dashboard</a>
        <span aria-hidden="true">/</span>
        <a href="<?= Helpers::url('admin/orders') ?>">Orders</a>
        <span aria-hidden="true">/</span>
        <span><?= htmlspecialchars((string)($order['order_number'] ?? '')) ?></span>
      </div>
      <h1>Order <em><?= htmlspecialchars((string)($order['order_number'] ?? '')) ?></em></h1>
      <p>Placed on <?= !empty($order['created_at']) ? date('d M Y, h:i A', strtotime($order['created_at'])) : date('d M Y, h:i A') ?></p>
    </div>
    <div style="display: flex; gap: 10px;">
      <a
        href="<?= Helpers::url('track?order=' . urlencode($order['order_number']) . '&token=' . urlencode($order['tracking_token'])) ?>"
        target="_blank"
        class="button button-outline"
        style="background: #fff; color: #073D26; border-color: #c4baa8;"
      >
        Customer Tracking View ↗
      </a>
    </div>
  </div>

  <div class="admin-two-cols">
    <!-- Left Column: Items and Customer Info -->
    <div class="admin-col">
      <!-- Items snapshot -->
      <div class="admin-card">
        <h2>Ordered Items (Snapshot)</h2>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Item</th>
                <th>Pack Size</th>
                <th>Unit Price</th>
                <th>Qty</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($items)): ?>
                <tr>
                  <td><strong>Standard Selection</strong></td>
                  <td>Standard Pack</td>
                  <td><?= Helpers::formatPaise((int)$order['total_paise']) ?></td>
                  <td>1</td>
                  <td><strong><?= Helpers::formatPaise((int)$order['total_paise']) ?></strong></td>
                </tr>
              <?php else: ?>
                <?php foreach ($items as $it): ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($it['product_name']) ?></strong></td>
                    <td><?= htmlspecialchars($it['variant_label']) ?></td>
                    <td><?= Helpers::formatPaise((int)$it['unit_price_paise']) ?></td>
                    <td><?= (int)$it['quantity'] ?></td>
                    <td><strong><?= Helpers::formatPaise((int)$it['unit_price_paise'] * (int)$it['quantity']) ?></strong></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <dl class="admin-order-totals">
          <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #f0ece3;">
            <dt style="color:#7C7265;">Subtotal</dt>
            <dd style="font-weight:600;"><?= Helpers::formatPaise((int)$order['subtotal_paise']) ?></dd>
          </div>
          <?php if ((int)$order['discount_paise'] > 0): ?>
            <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #f0ece3; color:#137333;">
              <dt>Discount (<?= htmlspecialchars($order['coupon_code'] ?? 'Coupon') ?>)</dt>
              <dd>−<?= Helpers::formatPaise((int)$order['discount_paise']) ?></dd>
            </div>
          <?php endif; ?>
          <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #f0ece3;">
            <dt style="color:#7C7265;">Shipping</dt>
            <dd style="font-weight:600;"><?= (int)$order['shipping_paise'] > 0 ? Helpers::formatPaise((int)$order['shipping_paise']) : 'Free' ?></dd>
          </div>
          <div style="display:flex; justify-content:space-between; padding:12px 0 0; font-size:18px; font-weight:700; color:#1E2520;">
            <dt>Final Total</dt>
            <dd><?= Helpers::formatPaise((int)$order['total_paise']) ?></dd>
          </div>
        </dl>
      </div>

      <!-- Customer & Delivery address -->
      <div class="admin-card">
        <h2>Customer &amp; Shipping Details</h2>
        <div class="admin-details-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
          <div>
            <span class="admin-detail-label" style="display:block; font-size:11px; text-transform:uppercase; color:#7C7265; font-weight:700;">Customer Name</span>
            <strong style="font-size:15px; color:#1E2520;"><?= htmlspecialchars($order['customer_name']) ?></strong>
          </div>
          <div>
            <span class="admin-detail-label" style="display:block; font-size:11px; text-transform:uppercase; color:#7C7265; font-weight:700;">Mobile Number</span>
            <strong style="font-size:15px; color:#1E2520;">+91 <?= htmlspecialchars($order['customer_phone']) ?></strong>
          </div>
          <div>
            <span class="admin-detail-label" style="display:block; font-size:11px; text-transform:uppercase; color:#7C7265; font-weight:700;">Email Address</span>
            <span><?= htmlspecialchars($order['customer_email'] ?? 'Not provided') ?></span>
          </div>
          <div>
            <span class="admin-detail-label" style="display:block; font-size:11px; text-transform:uppercase; color:#7C7265; font-weight:700;">Delivery Address</span>
            <p style="margin:4px 0 0; font-size:13.5px; color:#4A4031; line-height:1.4;">
              <?= htmlspecialchars($addr['street'] ?? $addr['line1'] ?? '') ?><br>
              <?php if (!empty($addr['landmark'])): ?>Landmark: <?= htmlspecialchars($addr['landmark']) ?><br><?php endif; ?>
              <?= htmlspecialchars($addr['city'] ?? '') ?>, <?= htmlspecialchars($addr['state'] ?? '') ?> — <strong><?= htmlspecialchars($addr['pincode'] ?? '') ?></strong>
            </p>
          </div>
        </div>
      </div>

      <!-- Payment History -->
      <div class="admin-card">
        <h2>Payment &amp; Gateway Information</h2>
        <div class="admin-details-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
          <div>
            <span class="admin-detail-label" style="display:block; font-size:11px; text-transform:uppercase; color:#7C7265; font-weight:700;">Payment Status</span>
            <div style="display:flex; align-items:center; gap:8px; margin-top:4px; flex-wrap:wrap;">
              <span class="badge <?= $order['payment_status'] === 'paid' ? 'badge-success' : ($order['payment_status'] === 'pending' ? 'badge-warning' : 'badge-danger') ?>">
                <?= strtoupper(htmlspecialchars((string)$order['payment_status'])) ?>
              </span>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="sync_cashfree">
                <button type="submit" class="button button-outline" style="font-size:11px; padding:3px 9px; background:#fff; color:#073D26; border-color:#d4cabc;" title="Query Cashfree API for latest status">
                  🔄 Check Cashfree
                </button>
              </form>
            </div>
          </div>
          <div>
            <span class="admin-detail-label" style="display:block; font-size:11px; text-transform:uppercase; color:#7C7265; font-weight:700;">Gateway Order ID</span>
            <code><?= htmlspecialchars($order['cashfree_order_id'] ?? '—') ?></code>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: Fulfillment, Shadowfax Dispatch, and Manual Refund -->
    <div class="admin-col">
      <!-- Quick Shadowfax Dispatch Box matching Node.js -->
      <div class="admin-card" style="background: #FAF7F2; border: 1px solid #D6C7B2;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px;">
          <div>
            <h2 style="margin: 0 0 4px; font-size: 16px; color: #073D26;">
              🚚 Shadowfax Logistics Partner
            </h2>
            <p style="margin: 0; font-size: 12.5px; color: #555;">
              Generate express waybill and arrange door-step delivery pickup.
            </p>
          </div>
          <form method="POST">
            <input type="hidden" name="action" value="dispatch_shadowfax">
            <button
              type="submit"
              <?= in_array($order['fulfillment_status'], ['shipped', 'delivered']) ? 'disabled' : '' ?>
              class="button button-primary"
              style="font-size: 12.5px; padding: 8px 14px; white-space: nowrap; background: #073D26; color: #E2C376;"
            >
              <?= in_array($order['fulfillment_status'], ['shipped', 'delivered']) ? '✓ Dispatched' : '⚡ 1-Click Shadowfax Dispatch' ?>
            </button>
          </form>
        </div>

        <?php if (!empty($order['tracking_number']) && $isShadowfax): ?>
          <div style="margin-top: 12px; padding: 8px 12px; background: #fff; border-radius: 6px; border: 1px solid #E0D6C5; font-size: 12.5px; display: flex; justify-content: space-between; align-items: center;">
            <span>Shadowfax AWB: <strong><?= htmlspecialchars($order['tracking_number']) ?></strong></span>
            <a
              href="https://tracker.shadowfax.in/#/track?awb=<?= urlencode($order['tracking_number']) ?>"
              target="_blank"
              rel="noreferrer"
              style="color: #073D26; font-weight: 600; text-decoration: underline;"
            >
              Track on Shadowfax ↗
            </a>
          </div>
        <?php endif; ?>
      </div>

      <!-- Fulfillment Update Form -->
      <div class="admin-card">
        <h2>Manual Fulfillment &amp; Dispatch</h2>
        <form class="admin-form" method="POST">
          <input type="hidden" name="action" value="update_fulfillment">

          <div class="form-group">
            <label for="fstatus">Fulfillment Status</label>
            <select id="fstatus" name="fulfillment_status">
              <option value="awaiting_payment" <?= $order['fulfillment_status'] === 'awaiting_payment' ? 'selected' : '' ?>>Awaiting Payment</option>
              <option value="confirmed" <?= $order['fulfillment_status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed (Payment Verified)</option>
              <option value="processing" <?= $order['fulfillment_status'] === 'processing' ? 'selected' : '' ?>>Processing (Selecting Stock)</option>
              <option value="packed" <?= $order['fulfillment_status'] === 'packed' ? 'selected' : '' ?>>Packed &amp; Sealed</option>
              <option value="shipped" <?= $order['fulfillment_status'] === 'shipped' ? 'selected' : '' ?>>Shipped (Dispatched)</option>
              <option value="delivered" <?= $order['fulfillment_status'] === 'delivered' ? 'selected' : '' ?>>Delivered to Customer</option>
              <option value="cancelled" <?= $order['fulfillment_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
          </div>

          <div class="form-group">
            <label for="courier">Courier / Delivery Partner</label>
            <select id="courier" name="courier">
              <option value="Shadowfax Express" <?= ($order['courier'] ?? '') === 'Shadowfax Express' ? 'selected' : '' ?>>Shadowfax Express (Recommended)</option>
              <option value="Blue Dart" <?= ($order['courier'] ?? '') === 'Blue Dart' ? 'selected' : '' ?>>Blue Dart</option>
              <option value="Delhivery" <?= ($order['courier'] ?? '') === 'Delhivery' ? 'selected' : '' ?>>Delhivery</option>
              <option value="DTDC" <?= ($order['courier'] ?? '') === 'DTDC' ? 'selected' : '' ?>>DTDC</option>
              <option value="India Post" <?= ($order['courier'] ?? '') === 'India Post' ? 'selected' : '' ?>>India Post Speed Post</option>
            </select>
          </div>

          <div class="form-group">
            <label for="tracknum">Courier Tracking / AWB Number</label>
            <input
              id="tracknum"
              name="tracking_number"
              value="<?= htmlspecialchars($order['tracking_number'] ?? '') ?>"
              placeholder="e.g. SFX2026123456 or AWB987654321"
            >
          </div>

          <button type="submit" class="button button-primary" style="background: #073D26; color: #fff;">
            Save Tracking &amp; Status
          </button>
        </form>
      </div>

      <!-- Record Manual Refund -->
      <div class="admin-card">
        <h2>Record Manual Refund</h2>
        <p class="admin-card-subtitle">
          Refunds are initiated manually via Cashfree. Record the verification details below to keep store records accurate.
        </p>

        <form class="admin-form" method="POST">
          <input type="hidden" name="action" value="record_refund">

          <div class="form-group">
            <label for="refamt">Refund Amount (₹) *</label>
            <input
              id="refamt"
              type="number"
              name="refund_amount"
              required
              min="1"
              step="0.01"
              placeholder="Max <?= $order['total_paise'] / 100 ?>"
            >
          </div>

          <div class="form-group">
            <label for="refnote">Reason / Cashfree Refund Reference *</label>
            <input
              id="refnote"
              name="refund_note"
              required
              placeholder="e.g. Customer requested cancellation / cf_rfnd_123"
            >
          </div>

          <div class="form-checkbox-row" style="margin-bottom: 16px;">
            <label class="checkbox-label" style="display:flex; gap:8px; align-items:center;">
              <input type="checkbox" name="mark_refunded" value="1" checked>
              <span>Mark order payment status as refunded</span>
            </label>
          </div>

          <button
            type="submit"
            class="button button-outline"
            style="background: #fff; color: #073D26; border-color: #c4baa8;"
          >
            Record Manual Refund
          </button>
        </form>

        <?php if (!empty($refunds)): ?>
          <div style="margin-top: 20px; border-top: 1px solid #ECE7DE; padding-top: 14px;">
            <h3 style="font-size: 14px; margin: 0 0 10px; color: #1E2520;">Recorded Refund History</h3>
            <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px;">
              <?php foreach ($refunds as $r): ?>
                <li style="display: flex; justify-content: space-between; align-items: center; font-size: 13px; background: #fdfbf7; padding: 8px 12px; border-radius: 6px;">
                  <div>
                    <strong><?= Helpers::formatPaise((int)$r['amount_paise']) ?></strong>
                    <span style="display: block; font-size: 11px; color: #777;"><?= htmlspecialchars($r['note'] ?? '') ?></span>
                  </div>
                  <span style="font-size: 11px; color: #888;">
                    <?= date('d M Y', strtotime($r['created_at'])) ?>
                  </span>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../layouts/admin_layout_bottom.php'; ?>
