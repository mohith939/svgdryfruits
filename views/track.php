<?php
/**
 * SVG Dry Fruits - Order Tracking View
 * Allows customer lookup via Order Number (SVG-...), Tracking Token, Mobile Number, or Email
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Models.php';

$query = trim($_GET['order_id'] ?? ($_GET['order'] ?? ($_GET['token'] ?? ($_GET['phone'] ?? ''))));
$order = null;
$ordersList = [];
$rateLimited = false;

if ($query !== '') {
    if (!Helpers::checkRateLimit('track_order', 30, 60)) {
        $rateLimited = true;
    } else {
        // 1. Direct match by SVG- order number
        if (strpos($query, 'SVG-') === 0) {
            $order = Models::getOrderByNumber($query);
        } else {
            // Check tracking token or order number
            $order = Models::getOrderByToken($query) ?: Models::getOrderByNumber($query);
        }

        // 2. If not found by order number or token, check if it's a mobile phone number
        if (!$order) {
            $digits = preg_replace('/\D/', '', $query);
            if (strlen($digits) >= 8) {
                $matchingByPhone = Models::getOrdersByPhone($query);
                if (count($matchingByPhone) === 1) {
                    $order = $matchingByPhone[0];
                } elseif (count($matchingByPhone) > 1) {
                    $ordersList = $matchingByPhone;
                }
            }
        }

        // 3. If still not found, check if it's an email address
        if (!$order && empty($ordersList) && strpos($query, '@') !== false) {
            $matchingByEmail = Models::getOrdersByEmail($query);
            if (count($matchingByEmail) === 1) {
                $order = $matchingByEmail[0];
            } elseif (count($matchingByEmail) > 1) {
                $ordersList = $matchingByEmail;
            }
        }
    }
}

$pageTitle = 'Track Your Order';
$activePage = 'track';

require __DIR__ . '/layouts/header.php';
?>

<div style="max-width:840px; margin:40px auto 140px; padding:0 20px 60px;">
  <div style="text-align:center; margin-bottom:35px;">
    <p style="font-size:11px; letter-spacing:0.25em; text-transform:uppercase; color:var(--clay); font-weight:700; margin-bottom:8px;">COURIER &amp; DISPATCH STATUS</p>
    <h1 style="font-family:var(--serif, serif); font-size:38px; color:var(--pine); margin:0 0 12px 0;">Track Your Order</h1>
    <p style="font-size:15px; color:var(--muted); max-width:550px; margin:0 auto; line-height:1.5;">
      Enter your <strong>Order Number</strong> (e.g. <code>SVG-20261102-1234</code>) or the <strong>10-Digit Mobile Number</strong> used during checkout.
    </p>
  </div>

  <!-- Search Form -->
  <form method="GET" action="<?= Helpers::url('track') ?>" style="display:flex; gap:10px; margin-bottom:35px;">
    <input
      type="text"
      name="order_id"
      value="<?= htmlspecialchars($query) ?>"
      placeholder="Enter Order ID or Mobile Number (e.g. 9876543210)"
      required
      style="flex:1; padding:14px 18px; border:1.5px solid var(--line); border-radius:6px; font-size:15px; background:var(--white); color:var(--ink);"
    >
    <button type="submit" style="background:var(--pine); color:#fff; border:none; padding:14px 28px; border-radius:6px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; font-size:13px; cursor:pointer; white-space:nowrap; box-shadow:0 2px 8px rgba(7,61,38,0.2);">
      Track Order &rarr;
    </button>
  </form>

  <?php if ($rateLimited): ?>
    <div style="text-align:center; padding:50px; background:#fff3cd; border:1px solid #ffeeba; border-radius:8px; color:#856404;">
      <h3 style="font-family:var(--serif, serif); font-size:24px; margin-bottom:8px;">Too Many Requests</h3>
      <p style="font-size:14px;">You have exceeded the search rate limit. Please wait a minute before searching again.</p>
    </div>

  <?php elseif ($query && !$order && empty($ordersList)): ?>
    <div style="text-align:center; padding:50px 30px; background:var(--white); border:1px solid var(--line); border-radius:8px; box-shadow:0 4px 16px rgba(0,0,0,0.03);">
      <div style="font-size:40px; margin-bottom:12px;">📦</div>
      <h3 style="font-family:var(--serif, serif); font-size:24px; color:var(--pine); margin-bottom:8px;">No Order Found</h3>
      <p style="color:var(--muted); font-size:14px; max-width:480px; margin:0 auto 20px; line-height:1.5;">
        We couldn't find an order with identifier "<strong><?= htmlspecialchars($query) ?></strong>". Please verify your Order Number or 10-digit mobile number and try again.
      </p>
      <a href="<?= Helpers::url('contact') ?>" style="display:inline-block; font-size:13px; color:var(--pine); font-weight:700; text-decoration:underline;">
        Need help? Contact Customer Support &rarr;
      </a>
    </div>

  <?php elseif (!empty($ordersList)): ?>
    <!-- Multiple Orders Found for this Mobile Number / Email -->
    <div style="background:var(--white); border:1px solid var(--line); border-radius:10px; padding:32px; box-shadow:0 8px 30px rgba(0,0,0,0.03);">
      <div style="border-bottom:1px solid var(--line); padding-bottom:16px; margin-bottom:24px;">
        <h2 style="font-family:var(--serif, serif); font-size:24px; color:var(--pine); margin:0 0 6px 0;">
          Orders Found for "<?= htmlspecialchars($query) ?>"
        </h2>
        <span style="font-size:13.5px; color:var(--muted);">
          Found <?= count($ordersList) ?> orders placed with this mobile number. Select an order to view its live courier status:
        </span>
      </div>

      <div style="display:flex; flex-direction:column; gap:16px;">
        <?php foreach ($ordersList as $o): 
          $fStatus = $o['fulfillment_status'];
          $isDelivered = ($fStatus === 'delivered');
          $itemsCount = count($o['items'] ?? []);
        ?>
          <div style="border:1.5px solid var(--line); border-radius:8px; padding:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; background:#FBF9F5; transition:border 0.2s;">
            <div>
              <div style="display:flex; align-items:center; gap:12px; margin-bottom:6px; flex-wrap:wrap;">
                <strong style="font-size:17px; color:var(--pine);"><?= htmlspecialchars($o['order_number']) ?></strong>
                <span style="padding:4px 12px; border-radius:12px; font-size:11px; font-weight:700; text-transform:uppercase; <?= $isDelivered ? 'background:#e6f4ea; color:#137333;' : 'background:#edf3ef; color:var(--pine);' ?>">
                  <?= ucwords(str_replace('_', ' ', $fStatus)) ?>
                </span>
                <span style="font-size:12px; color:var(--muted);">
                  <?= date('d M Y, h:i A', strtotime($o['created_at'])) ?>
                </span>
              </div>
              <p style="margin:0; font-size:13px; color:var(--muted);">
                <?= $itemsCount ?> items &bull; Total: <strong style="color:var(--pine);"><?= Helpers::formatPaise((int)($o['total_paise'] ?? ($o['total_amount_paise'] ?? 0))) ?></strong>
              </p>
              <?php if (!empty($o['courier'])): ?>
                <p style="margin:4px 0 0; font-size:12.5px; color:#073D26;">
                  Courier: <strong><?= htmlspecialchars($o['courier']) ?></strong>
                  <?php if (!empty($o['tracking_number'])): ?>
                    &bull; AWB: <code><?= htmlspecialchars($o['tracking_number']) ?></code>
                  <?php endif; ?>
                </p>
              <?php endif; ?>
            </div>
            <a
              href="<?= Helpers::url('track?order_id=' . urlencode($o['order_number'])) ?>"
              style="background:var(--pine); color:#fff; padding:10px 22px; border-radius:6px; font-size:13px; font-weight:700; text-decoration:none; white-space:nowrap; box-shadow:0 2px 6px rgba(7,61,38,0.2);"
            >
              View Tracking Details &rarr;
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  <?php elseif ($order): ?>
    <?php
      $fStatus = $order['fulfillment_status'];
      $isDelivered = ($fStatus === 'delivered');
      $isDispatched = ($fStatus === 'dispatched' || $isDelivered);
      $isProcessing = ($fStatus === 'processing' || $isDispatched);
      $isAwaiting = true;
      $addr = $order['shipping_address'] ?? [];
    ?>
    <div style="background:var(--white); border:1px solid var(--line); border-radius:10px; padding:35px; box-shadow:0 8px 30px rgba(0,0,0,0.03);">
      <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--line); padding-bottom:20px; margin-bottom:30px; flex-wrap:wrap; gap:12px;">
        <div>
          <span style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:var(--clay); display:block; margin-bottom:4px;">
            ORDER DETAILS
          </span>
          <h2 style="font-family:var(--serif, serif); font-size:28px; color:var(--pine); margin:0 0 6px 0;"><?= htmlspecialchars($order['order_number']) ?></h2>
          <span style="font-size:12.5px; color:var(--muted);">Placed on <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></span>
        </div>
        <div style="text-align:right;">
          <span style="display:inline-block; padding:6px 16px; border-radius:20px; font-size:12.5px; font-weight:700; text-transform:uppercase; <?= $isDelivered ? 'background:#e6f4ea; color:#137333;' : 'background:#edf3ef; color:var(--pine);' ?>">
            <?= ucwords(str_replace('_', ' ', $fStatus)) ?>
          </span>
          <span style="display:block; font-size:12px; color:var(--muted); margin-top:4px;">
            Payment: <strong><?= strtoupper($order['payment_status'] ?? 'paid') ?></strong>
          </span>
        </div>
      </div>

      <!-- Stepper Indicator -->
      <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:10px; text-align:center; margin-bottom:35px; position:relative;">
        <div style="padding:12px 6px; border-radius:6px; <?= $isAwaiting ? 'background:#edf3ef; font-weight:700; color:var(--pine);' : 'color:var(--muted);' ?>">
          <div style="font-size:18px; margin-bottom:4px;">1</div>
          <div style="font-size:12px;">Order Placed</div>
        </div>
        <div style="padding:12px 6px; border-radius:6px; <?= $isProcessing ? 'background:#edf3ef; font-weight:700; color:var(--pine);' : 'color:var(--muted);' ?>">
          <div style="font-size:18px; margin-bottom:4px;">2</div>
          <div style="font-size:12px;">Packing &amp; Processing</div>
        </div>
        <div style="padding:12px 6px; border-radius:6px; <?= $isDispatched ? 'background:#edf3ef; font-weight:700; color:var(--pine);' : 'color:var(--muted);' ?>">
          <div style="font-size:18px; margin-bottom:4px;">3</div>
          <div style="font-size:12px;">Dispatched</div>
        </div>
        <div style="padding:12px 6px; border-radius:6px; <?= $isDelivered ? 'background:#e6f4ea; font-weight:700; color:#137333;' : 'color:var(--muted);' ?>">
          <div style="font-size:18px; margin-bottom:4px;">4</div>
          <div style="font-size:12px;">Delivered</div>
        </div>
      </div>

      <!-- Courier / Dispatch Tracking Info Banner -->
      <?php if (!empty($order['courier']) || !empty($order['tracking_number'])): ?>
        <div style="background:#eaf4ee; border:1px solid #c7e5cf; border-radius:8px; padding:18px 22px; margin-bottom:30px; font-size:14px; color:var(--pine); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
          <div>
            <?php if (!empty($order['courier'])): ?>
              <span style="display:block; margin-bottom:3px;">Courier Partner: <strong><?= htmlspecialchars($order['courier']) ?></strong></span>
            <?php endif; ?>
            <?php if (!empty($order['tracking_number'])): ?>
              <span>Tracking / AWB Number: <code style="background:#fff; padding:2px 8px; border-radius:4px; border:1px solid #c7e5cf; font-weight:700;"><?= htmlspecialchars($order['tracking_number']) ?></code></span>
            <?php endif; ?>
          </div>
          <?php if (!empty($order['courier']) && stripos($order['courier'], 'shadowfax') !== false && !empty($order['tracking_number'])): ?>
            <a href="https://tracker.shadowfax.in/#/track?awb=<?= urlencode($order['tracking_number']) ?>" target="_blank" rel="noopener noreferrer" style="background:#073D26; color:#fff; padding:8px 16px; border-radius:6px; font-size:12.5px; font-weight:700; text-decoration:none;">
              Track on Shadowfax ↗
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- Delivery Details Grid -->
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:20px; background:#FBF9F5; border:1px solid var(--line); border-radius:8px; padding:20px; margin-bottom:30px;">
        <div>
          <span style="font-size:11px; text-transform:uppercase; letter-spacing:0.08em; color:var(--muted); font-weight:700; display:block; margin-bottom:6px;">Delivery To</span>
          <strong style="color:var(--pine); font-size:15px;"><?= htmlspecialchars($order['customer_name'] ?? 'Customer') ?></strong>
          <?php 
            $rawPhone = (string)($order['customer_phone'] ?? '');
            $cleanDigits = preg_replace('/\D/', '', $rawPhone);
            if (strlen($cleanDigits) === 10) {
              $formattedPhone = '+91 ' . $cleanDigits;
            } elseif (str_starts_with($rawPhone, '+')) {
              $formattedPhone = $rawPhone;
            } elseif (strlen($cleanDigits) > 10) {
              $formattedPhone = '+' . $cleanDigits;
            } else {
              $formattedPhone = '+91 ' . $cleanDigits;
            }
          ?>
          <span style="display:block; font-size:13px; color:var(--muted); margin-top:2px;">Phone: <?= htmlspecialchars($formattedPhone) ?></span>
          <?php if (!empty($order['customer_email'])): ?>
            <span style="display:block; font-size:13px; color:var(--muted);"><?= htmlspecialchars($order['customer_email']) ?></span>
          <?php endif; ?>
        </div>
        <div>
          <span style="font-size:11px; text-transform:uppercase; letter-spacing:0.08em; color:var(--muted); font-weight:700; display:block; margin-bottom:6px;">Shipping Address</span>
          <span style="font-size:13.5px; color:var(--ink); line-height:1.5;">
            <?php 
              $street = $addr['street'] ?? ($addr['line1'] ?? ($addr['address'] ?? ''));
              $landmark = $addr['landmark'] ?? '';
              $city = $addr['city'] ?? '';
              $state = $addr['state'] ?? '';
              $pincode = $addr['pincode'] ?? ($addr['postal_code'] ?? '');
              $cityState = trim(($city ? $city : '') . ($state ? ', ' . $state : ''), ', ');
            ?>
            <?php if (!empty($street)): ?>
              <?= htmlspecialchars($street) ?><br>
            <?php endif; ?>
            <?php if (!empty($landmark)): ?>
              Landmark: <?= htmlspecialchars($landmark) ?><br>
            <?php endif; ?>
            <?= htmlspecialchars($cityState) ?><?= !empty($pincode) ? ' – ' . htmlspecialchars((string)$pincode) : '' ?>
          </span>
        </div>
      </div>

      <!-- Items in Order -->
      <h3 style="font-family:var(--serif, serif); font-size:20px; color:var(--pine); margin:0 0 14px 0;">Items Ordered</h3>
      <div style="border-top:1px solid var(--line); margin-bottom:20px;">
        <?php foreach ($order['items'] as $item): ?>
          <div style="display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid var(--line); font-size:14px;">
            <span><?= htmlspecialchars($item['product_name']) ?> (<?= htmlspecialchars($item['variant_label']) ?> &times; <?= $item['quantity'] ?>)</span>
            <strong><?= Helpers::formatPaise((int)$item['unit_price_paise'] * $item['quantity']) ?></strong>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Order Pricing Summary Card -->
      <div style="background:#F8FBF9; border:1.5px solid #073D26; border-radius:10px; padding:22px 24px; margin-top:24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; border-bottom:1px solid #DFD7CA; padding-bottom:10px; flex-wrap:wrap; gap:8px;">
          <span style="font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:0.1em; color:var(--clay);">
            Order Payment Summary
          </span>
          <span style="font-size:12px; font-weight:700; padding:4px 12px; border-radius:12px; background:#e6f4ea; color:#137333;">
            ✓ <?= strtoupper(htmlspecialchars($order['payment_status'] ?? 'paid')) ?> VIA CASHFREE
          </span>
        </div>

        <div style="display:flex; justify-content:space-between; font-size:14.5px; padding:5px 0;">
          <span style="color:var(--muted);">Subtotal (<?= count($order['items']) ?> item<?= count($order['items']) > 1 ? 's' : '' ?>):</span>
          <strong><?= Helpers::formatPaise((int)($order['subtotal_paise'] ?? 0)) ?></strong>
        </div>

        <?php if (!empty($order['discount_paise'])): ?>
          <div style="display:flex; justify-content:space-between; font-size:14.5px; padding:5px 0; color:#137333;">
            <span>Discount Applied:</span>
            <strong>-<?= Helpers::formatPaise((int)$order['discount_paise']) ?></strong>
          </div>
        <?php endif; ?>

        <div style="display:flex; justify-content:space-between; font-size:14.5px; padding:5px 0;">
          <span style="color:var(--muted);">Delivery &amp; Shipping:</span>
          <strong style="color:<?= ((int)($order['shipping_paise'] ?? 0) === 0) ? '#137333' : 'var(--ink)' ?>;">
            <?= ((int)($order['shipping_paise'] ?? 0) === 0) ? 'FREE' : Helpers::formatPaise((int)$order['shipping_paise']) ?>
          </strong>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; border-top:2px solid #073D26; padding-top:14px; margin-top:10px; flex-wrap:wrap; gap:8px;">
          <span style="font-size:17px; font-weight:800; color:#073D26;">Grand Total Paid:</span>
          <span style="font-size:24px; font-weight:900; color:#073D26; letter-spacing:-0.02em;">
            <?= Helpers::formatPaise((int)($order['total_paise'] ?? ($order['total_amount_paise'] ?? 0))) ?>
          </span>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/layouts/footer.php'; ?>
