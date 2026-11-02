<?php
/**
 * SVG Dry Fruits - Order Tracking View
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Models.php';

$query = trim($_GET['order_id'] ?? ($_GET['token'] ?? ''));
$order = null;

if ($query) {
    if (strpos($query, 'SVG-') === 0) {
        $order = Models::getOrderByNumber($query);
    } else {
        $order = Models::getOrderByToken($query) ?: Models::getOrderByNumber($query);
    }
}

$pageTitle = 'Track Your Order';
$activePage = 'track';

require __DIR__ . '/layouts/header.php';
?>

<div style="max-width:800px; margin:40px auto 80px; padding:0 20px;">
  <div style="text-align:center; margin-bottom:40px;">
    <p style="font-size:11px; letter-spacing:0.25em; text-transform:uppercase; color:var(--clay); font-weight:700; margin-bottom:8px;">COURIER &amp; DISPATCH STATUS</p>
    <h1 style="font-family:var(--serif, serif); font-size:38px; color:var(--pine); margin:0 0 14px 0;">Track Your Order</h1>
    <p style="font-size:15px; color:var(--muted); max-width:500px; margin:0 auto;">
      Enter your Order Number (e.g. SVG-20261102-1234) or the tracking token received upon checkout.
    </p>
  </div>

  <!-- Search Form -->
  <form method="GET" action="<?= Helpers::url('track') ?>" style="display:flex; gap:10px; margin-bottom:40px;">
    <input type="text" name="order_id" value="<?= htmlspecialchars($query) ?>" placeholder="Enter Order ID or Tracking Token" required 
           style="flex:1; padding:14px 18px; border:1px solid var(--line); border-radius:4px; font-size:15px; background:var(--white);">
    <button type="submit" style="background:var(--pine); color:#fff; border:none; padding:14px 28px; border-radius:4px; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; font-size:13px; cursor:pointer;">
      Track &rarr;
    </button>
  </form>

  <?php if ($query && !$order): ?>
    <div style="text-align:center; padding:50px; background:var(--white); border:1px solid var(--line); border-radius:6px;">
      <h3 style="font-family:var(--serif, serif); font-size:24px; color:var(--pine); margin-bottom:8px;">No Order Found</h3>
      <p style="color:var(--muted); font-size:14px;">We couldn't find an order with identifier "<strong><?= htmlspecialchars($query) ?></strong>". Please verify and try again.</p>
    </div>
  <?php elseif ($order): ?>
    <?php
      $fStatus = $order['fulfillment_status'];
      $isDelivered = ($fStatus === 'delivered');
      $isDispatched = ($fStatus === 'dispatched' || $isDelivered);
      $isProcessing = ($fStatus === 'processing' || $isDispatched);
      $isAwaiting = true;
    ?>
    <div style="background:var(--white); border:1px solid var(--line); border-radius:8px; padding:35px; box-shadow:0 8px 30px rgba(0,0,0,0.03);">
      <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--line); padding-bottom:20px; margin-bottom:30px;">
        <div>
          <h2 style="font-family:var(--serif, serif); font-size:26px; color:var(--pine); margin:0 0 6px 0;"><?= htmlspecialchars($order['order_number']) ?></h2>
          <span style="font-size:12px; color:var(--muted);">Placed on <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></span>
        </div>
        <span style="padding:6px 14px; border-radius:20px; font-size:12px; font-weight:700; text-transform:uppercase; <?= $isDelivered ? 'background:#e6f4ea; color:#137333;' : 'background:#edf3ef; color:var(--pine);' ?>">
          <?= ucwords(str_replace('_', ' ', $fStatus)) ?>
        </span>
      </div>

      <!-- Stepper Indicator -->
      <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:10px; text-align:center; margin-bottom:35px; position:relative;">
        <div style="padding:10px; border-radius:4px; <?= $isAwaiting ? 'background:#edf3ef; font-weight:700; color:var(--pine);' : 'color:var(--muted);' ?>">
          <div style="font-size:18px; margin-bottom:4px;">1</div>
          <div style="font-size:12px;">Order Placed</div>
        </div>
        <div style="padding:10px; border-radius:4px; <?= $isProcessing ? 'background:#edf3ef; font-weight:700; color:var(--pine);' : 'color:var(--muted);' ?>">
          <div style="font-size:18px; margin-bottom:4px;">2</div>
          <div style="font-size:12px;">Packing &amp; Processing</div>
        </div>
        <div style="padding:10px; border-radius:4px; <?= $isDispatched ? 'background:#edf3ef; font-weight:700; color:var(--pine);' : 'color:var(--muted);' ?>">
          <div style="font-size:18px; margin-bottom:4px;">3</div>
          <div style="font-size:12px;">Dispatched</div>
        </div>
        <div style="padding:10px; border-radius:4px; <?= $isDelivered ? 'background:#e6f4ea; font-weight:700; color:#137333;' : 'color:var(--muted);' ?>">
          <div style="font-size:18px; margin-bottom:4px;">4</div>
          <div style="font-size:12px;">Delivered</div>
        </div>
      </div>

      <?php if (!empty($order['courier']) && !empty($order['tracking_number'])): ?>
        <div style="background:#eaf4ee; border:1px solid #c7e5cf; border-radius:6px; padding:16px 20px; margin-bottom:30px; font-size:14px; color:var(--pine);">
          <strong>Courier Partner:</strong> <?= htmlspecialchars($order['courier']) ?> &bull; 
          <strong>Tracking Number:</strong> <code><?= htmlspecialchars($order['tracking_number']) ?></code>
        </div>
      <?php endif; ?>

      <!-- Items in Order -->
      <h3 style="font-family:var(--serif, serif); font-size:20px; color:var(--pine); margin:0 0 14px 0;">Items Ordered</h3>
      <div style="border-top:1px solid var(--line); margin-bottom:24px;">
        <?php foreach ($order['items'] as $item): ?>
          <div style="display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid var(--line); font-size:14px;">
            <span><?= htmlspecialchars($item['product_name']) ?> (<?= htmlspecialchars($item['variant_label']) ?> &times; <?= $item['quantity'] ?>)</span>
            <strong><?= Helpers::formatPaise((int)$item['unit_price_paise'] * $item['quantity']) ?></strong>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/layouts/footer.php'; ?>
