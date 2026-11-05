<?php
/**
 * SVG Dry Fruits - Order Success View
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Models.php';
require_once ROOT_PATH . '/src/Auth.php';

$orderNumber = trim($_GET['order_id'] ?? '');
$token = trim($_GET['token'] ?? '');

$order = null;
if ($orderNumber) {
    $order = Models::getOrderByNumber($orderNumber);
} elseif ($token) {
    $order = Models::getOrderByToken($token);
}

$isAuthorized = false;
if ($order) {
    if (Auth::check()) {
        $isAuthorized = true;
    } elseif (!empty($token) && hash_equals($order['tracking_token'] ?? '', $token)) {
        $isAuthorized = true;
    } elseif (!empty($_SESSION['recent_order_id']) && $_SESSION['recent_order_id'] === $order['order_number']) {
        $isAuthorized = true;
    }
}

// Server-side instant auto-fetch from Cashfree
if ($order && $order['payment_status'] !== 'paid') {
    try {
        require_once ROOT_PATH . '/config/cashfree.php';
        $cashfree = new CashfreePG();
        $cfOrder = $cashfree->getOrder($order['order_number']);
        $cfStatus = strtoupper($cfOrder['order_status'] ?? '');
        $cfAmount = (float)($cfOrder['order_amount'] ?? 0);
        $expectedAmount = Helpers::paiseToRupees((int)$order['total_paise']);
        if ($cfStatus === 'PAID' && abs($cfAmount - $expectedAmount) <= 0.01) {
            Models::updateOrderPayment($order['order_number'], 'paid', $cfOrder['cf_order_id'] ?? null);
            $order['payment_status'] = 'paid';
            $order['fulfillment_status'] = 'processing';
            $order['cashfree_order_id'] = $cfOrder['cf_order_id'] ?? ($order['cashfree_order_id'] ?? null);
        } elseif ($cfStatus === 'PAID') {
            error_log("CRITICAL PAYMENT TAMPERING in order_success for order {$order['order_number']}: expected ₹{$expectedAmount}, got ₹{$cfAmount}");
        }
    } catch (Throwable $e) {
        error_log("Order success auto-verification notice: " . $e->getMessage());
    }
}

$pageTitle = 'Order Confirmed';
require __DIR__ . '/layouts/header.php';
?>

<div style="max-width:800px; margin:50px auto 80px; padding:0 20px;">
  <?php if (!$order): ?>
    <div style="text-align:center; padding:60px 20px; background:var(--white); border:1px solid var(--line); border-radius:6px;">
      <h2 style="font-family:var(--serif, serif); font-size:32px; color:var(--pine);">Order Not Found</h2>
      <p style="color:var(--muted); font-size:14px; margin-bottom:20px;">We could not find the order matching your reference.</p>
      <a href="<?= Helpers::url('/') ?>" style="background:var(--pine); color:#fff; padding:12px 24px; border-radius:4px; font-weight:600; text-transform:uppercase; font-size:12px;">Return Home</a>
    </div>
  <?php else: ?>
    <!-- Verification Status Checker for Cashfree -->
    <script>
      (function() {
        const orderId = '<?= htmlspecialchars($order['order_number']) ?>';
        const isPaid = <?= ($order['payment_status'] === 'paid') ? 'true' : 'false' ?>;
        if (!isPaid) {
          fetch(window.SVG_APP.apiUrl + '/verify.php?order_id=' + encodeURIComponent(orderId))
            .then(r => r.json())
            .then(res => {
              if (res.success && res.payment_status === 'paid') {
                const badge = document.getElementById('payment-status-badge');
                if (badge) {
                  badge.textContent = 'PAID (ONLINE)';
                  badge.style.background = '#e6f4ea';
                  badge.style.color = '#137333';
                }
              }
            });
        }
      })();
    </script>

    <div style="background:var(--white); border:1px solid var(--line); border-radius:8px; padding:40px; box-shadow:0 8px 30px rgba(0,0,0,0.03);">
      <div style="text-align:center; margin-bottom:35px;">
        <div style="width:64px; height:64px; background:#e6f4ea; color:#137333; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:32px; margin-bottom:16px;">
          ✓
        </div>
        <p style="font-size:12px; letter-spacing:0.2em; text-transform:uppercase; color:var(--clay); font-weight:700; margin:0 0 8px 0;">THANK YOU FOR YOUR ORDER</p>
        <h1 style="font-family:var(--serif, serif); font-size:38px; color:var(--pine); margin:0 0 10px 0;">Order Confirmed!</h1>
        <p style="font-size:15px; color:var(--muted); margin:0;">
          Order Number: <strong style="color:var(--ink);"><?= htmlspecialchars($order['order_number']) ?></strong>
        </p>
      </div>

      <!-- Key Details Summary -->
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; background:var(--paper); padding:20px; border-radius:6px; margin-bottom:30px; font-size:13px;">
        <div>
          <span style="color:var(--muted); display:block; margin-bottom:4px;">Delivery To:</span>
          <?php if ($isAuthorized): ?>
            <strong><?= htmlspecialchars($order['customer_name']) ?></strong><br>
            <?= htmlspecialchars($order['shipping_address']['street'] ?? '') ?><br>
            <?= htmlspecialchars($order['shipping_address']['city'] ?? '') ?>, <?= htmlspecialchars($order['shipping_address']['state'] ?? '') ?> - <?= htmlspecialchars($order['shipping_address']['pincode'] ?? '') ?><br>
            Phone: +91 <?= htmlspecialchars($order['customer_phone']) ?>
          <?php else: ?>
            <strong><?= htmlspecialchars(substr($order['customer_name'], 0, 1) . str_repeat('*', max(1, strlen($order['customer_name']) - 1))) ?></strong><br>
            <?= htmlspecialchars($order['shipping_address']['city'] ?? '') ?>, <?= htmlspecialchars($order['shipping_address']['state'] ?? '') ?><br>
            Phone: +91 ******<?= htmlspecialchars(substr($order['customer_phone'], -4)) ?><br>
            <span style="font-size:11px; color:#888;">(Sign in or use your private order link to view full delivery address)</span>
          <?php endif; ?>
        </div>
        <div>
          <span style="color:var(--muted); display:block; margin-bottom:4px;">Payment Status:</span>
          <span id="payment-status-badge" style="display:inline-block; padding:4px 10px; border-radius:4px; font-weight:700; font-size:12px; text-transform:uppercase; <?= ($order['payment_status'] === 'paid') ? 'background:#e6f4ea; color:#137333;' : 'background:#fff8e1; color:#b06000;' ?>">
            <?= strtoupper(htmlspecialchars($order['payment_status'])) ?>
          </span>
          <?php if ($isAuthorized): ?>
            <div style="margin-top:14px;">
              <span style="color:var(--muted); display:block; margin-bottom:4px;">Tracking Reference:</span>
              <code><?= htmlspecialchars($order['tracking_token']) ?></code>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Items List -->
      <h3 style="font-family:var(--serif, serif); font-size:22px; color:var(--pine); margin:0 0 16px 0;">Order Items</h3>
      <div style="border-top:1px solid var(--line); margin-bottom:24px;">
        <?php foreach ($order['items'] as $item): ?>
          <div style="display:flex; justify-content:space-between; padding:14px 0; border-bottom:1px solid var(--line); font-size:14px;">
            <div>
              <strong><?= htmlspecialchars($item['product_name']) ?></strong>
              <span style="color:var(--muted); font-size:12px; margin-left:8px;">(<?= htmlspecialchars($item['variant_label']) ?> &times; <?= $item['quantity'] ?>)</span>
            </div>
            <strong><?= Helpers::formatPaise((int)$item['unit_price_paise'] * $item['quantity']) ?></strong>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Total -->
      <div style="display:flex; justify-content:space-between; align-items:center; font-size:18px; font-weight:700; color:var(--pine); margin-bottom:35px;">
        <span>Total Paid</span>
        <span><?= Helpers::formatPaise((int)$order['total_paise']) ?></span>
      </div>

      <!-- Actions -->
      <div style="display:flex; gap:16px; justify-content:center; flex-wrap:wrap;">
        <a href="<?= Helpers::url('track?order_id=' . $order['order_number']) ?>" style="background:var(--pine); color:#fff; padding:14px 28px; border-radius:4px; font-weight:600; font-size:13px; text-transform:uppercase;">
          Track Delivery Status &rarr;
        </a>
        <a href="<?= Helpers::url('shop') ?>" style="border:1px solid var(--line); padding:14px 28px; border-radius:4px; font-weight:600; font-size:13px; text-transform:uppercase;">
          Continue Shopping
        </a>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/layouts/footer.php'; ?>
