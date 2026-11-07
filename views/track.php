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

<div style="max-width:800px; margin:32px auto 160px; padding:0 20px 60px;">
  <div style="text-align:center; margin-bottom:26px;">
    <h1 style="font-family:var(--serif, serif); font-size:32px; color:var(--pine); margin:0 0 6px 0;">Track Your Order</h1>
    <p style="font-size:14px; color:var(--muted); max-width:480px; margin:0 auto; line-height:1.45;">
      Enter your <strong>Order ID</strong> (e.g. <code>SVG-...</code>) or <strong>10-digit mobile number</strong>:
    </p>
  </div>

  <!-- Search Form -->
  <form method="GET" action="<?= Helpers::url('track') ?>" style="display:flex; gap:10px; margin-bottom:28px;">
    <input
      type="text"
      name="order_id"
      value="<?= htmlspecialchars($query) ?>"
      placeholder="Order ID (SVG-...) or Mobile (e.g. 9876543210)"
      required
      style="flex:1; padding:12px 16px; border:1.5px solid var(--line); border-radius:6px; font-size:14.5px; background:var(--white); color:var(--ink);"
    >
    <button type="submit" style="background:var(--pine); color:#fff; border:none; padding:12px 24px; border-radius:6px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; font-size:13px; cursor:pointer; white-space:nowrap; box-shadow:0 2px 8px rgba(7,61,38,0.18);">
      Track &rarr;
    </button>
  </form>

  <?php if ($rateLimited): ?>
    <div style="text-align:center; padding:40px; background:#fff3cd; border:1px solid #ffeeba; border-radius:8px; color:#856404;">
      <h3 style="font-family:var(--serif, serif); font-size:22px; margin-bottom:8px;">Too Many Requests</h3>
      <p style="font-size:14px;">You have exceeded the search rate limit. Please wait a minute before searching again.</p>
    </div>

  <?php elseif ($query && !$order && empty($ordersList)): ?>
    <div style="text-align:center; padding:44px 24px; background:var(--white); border:1px solid var(--line); border-radius:8px; box-shadow:0 4px 16px rgba(0,0,0,0.03);">
      <div style="font-size:36px; margin-bottom:10px;">📦</div>
      <h3 style="font-family:var(--serif, serif); font-size:22px; color:var(--pine); margin-bottom:6px;">No Order Found</h3>
      <p style="color:var(--muted); font-size:14px; max-width:440px; margin:0 auto 18px; line-height:1.45;">
        No active orders found for "<strong><?= htmlspecialchars($query) ?></strong>". Please check your Order ID or mobile number.
      </p>
      <a href="<?= Helpers::url('contact') ?>" style="display:inline-block; font-size:13px; color:var(--pine); font-weight:700; text-decoration:underline;">
        Need help? Contact Customer Support &rarr;
      </a>
    </div>

  <?php elseif (!empty($ordersList)): ?>
    <!-- Multiple Orders List -->
    <div style="background:var(--white); border:1px solid var(--line); border-radius:10px; padding:28px; box-shadow:0 6px 24px rgba(0,0,0,0.03);">
      <div style="border-bottom:1px solid var(--line); padding-bottom:14px; margin-bottom:20px;">
        <h2 style="font-family:var(--serif, serif); font-size:22px; color:var(--pine); margin:0 0 4px 0;">
          Orders for "<?= htmlspecialchars($query) ?>"
        </h2>
        <span style="font-size:13px; color:var(--muted);">
          Found <?= count($ordersList) ?> orders. Select one to view courier tracking:
        </span>
      </div>

      <div style="display:flex; flex-direction:column; gap:12px;">
        <?php foreach ($ordersList as $o): 
          $fStatus = $o['fulfillment_status'];
          $isDelivered = ($fStatus === 'delivered');
        ?>
          <div style="border:1px solid var(--line); border-radius:8px; padding:16px 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px; background:#FBF9F5;">
            <div>
              <div style="display:flex; align-items:center; gap:10px; margin-bottom:4px; flex-wrap:wrap;">
                <strong style="font-size:16px; color:var(--pine);"><?= htmlspecialchars($o['order_number']) ?></strong>
                <span style="padding:3px 10px; border-radius:12px; font-size:11px; font-weight:700; text-transform:uppercase; <?= $isDelivered ? 'background:#e6f4ea; color:#137333;' : 'background:#edf3ef; color:var(--pine);' ?>">
                  <?= ucwords(str_replace('_', ' ', $fStatus)) ?>
                </span>
                <span style="font-size:12px; color:var(--muted);">
                  <?= date('d M Y', strtotime($o['created_at'])) ?>
                </span>
              </div>
              <p style="margin:0; font-size:13px; color:var(--muted);">
                Total: <strong style="color:var(--pine);"><?= Helpers::formatPaise((int)($o['total_paise'] ?? ($o['total_amount_paise'] ?? 0))) ?></strong>
                <?php if (!empty($o['courier'])): ?>
                  &bull; Courier: <strong><?= htmlspecialchars($o['courier']) ?></strong>
                <?php endif; ?>
              </p>
            </div>
            <a
              href="<?= Helpers::url('track?order_id=' . urlencode($o['order_number'])) ?>"
              style="background:var(--pine); color:#fff; padding:8px 18px; border-radius:6px; font-size:12.5px; font-weight:700; text-decoration:none;"
            >
              View Tracking &rarr;
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
    <div style="background:var(--white); border:1px solid var(--line); border-radius:10px; padding:28px 30px; box-shadow:0 6px 24px rgba(0,0,0,0.03);">
      <!-- Order Header -->
      <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--line); padding-bottom:16px; margin-bottom:24px; flex-wrap:wrap; gap:10px;">
        <div>
          <span style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:var(--clay); display:block; margin-bottom:2px;">
            ORDER DETAILS
          </span>
          <h2 style="font-family:var(--serif, serif); font-size:24px; color:var(--pine); margin:0 0 4px 0;">
            <?= htmlspecialchars($order['order_number']) ?>
          </h2>
          <span style="font-size:12px; color:var(--muted);">
            Placed on <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?>
          </span>
        </div>
        <div style="text-align:right;">
          <span style="display:inline-block; padding:5px 14px; border-radius:20px; font-size:12px; font-weight:700; text-transform:uppercase; <?= $isDelivered ? 'background:#e6f4ea; color:#137333;' : 'background:#edf3ef; color:var(--pine);' ?>">
            <?= ucwords(str_replace('_', ' ', $fStatus)) ?>
          </span>
          <span style="display:block; font-size:11.5px; color:var(--muted); margin-top:3px;">
            Paid via Cashfree
          </span>
        </div>
      </div>

      <!-- Minimal Connected Progress Stepper -->
      <div style="margin-bottom:26px; padding:16px 14px; background:#FBF9F5; border:1px solid var(--line); border-radius:8px;">
        <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:6px; text-align:center;">
          <div style="padding:6px; border-radius:6px; <?= $isAwaiting ? 'background:#edf3ef; font-weight:700; color:var(--pine);' : 'color:var(--muted);' ?>">
            <div style="font-size:14px; margin-bottom:2px;">✓ 1</div>
            <div style="font-size:12px;">Placed</div>
          </div>
          <div style="padding:6px; border-radius:6px; <?= $isProcessing ? 'background:#edf3ef; font-weight:700; color:var(--pine);' : 'color:var(--muted);' ?>">
            <div style="font-size:14px; margin-bottom:2px;"><?= $isProcessing ? '✓' : '○' ?> 2</div>
            <div style="font-size:12px;">Packed</div>
          </div>
          <div style="padding:6px; border-radius:6px; <?= $isDispatched ? 'background:#edf3ef; font-weight:700; color:var(--pine);' : 'color:var(--muted);' ?>">
            <div style="font-size:14px; margin-bottom:2px;"><?= $isDispatched ? '✓' : '○' ?> 3</div>
            <div style="font-size:12px;">Dispatched</div>
          </div>
          <div style="padding:6px; border-radius:6px; <?= $isDelivered ? 'background:#e6f4ea; font-weight:700; color:#137333;' : 'color:var(--muted);' ?>">
            <div style="font-size:14px; margin-bottom:2px;"><?= $isDelivered ? '✓' : '○' ?> 4</div>
            <div style="font-size:12px;">Delivered</div>
          </div>
        </div>
      </div>

      <!-- Courier / AWB Banner -->
      <?php if (!empty($order['courier']) || !empty($order['tracking_number'])): ?>
        <div style="background:#eaf4ee; border:1px solid #c7e5cf; border-radius:8px; padding:14px 18px; margin-bottom:24px; font-size:13.5px; color:var(--pine); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
          <div>
            <?php if (!empty($order['courier'])): ?>
              <span>Courier: <strong><?= htmlspecialchars($order['courier']) ?></strong></span>
            <?php endif; ?>
            <?php if (!empty($order['tracking_number'])): ?>
              <span style="margin-left:8px;">AWB: <code style="background:#fff; padding:2px 6px; border-radius:4px; border:1px solid #c7e5cf; font-weight:700;"><?= htmlspecialchars($order['tracking_number']) ?></code></span>
            <?php endif; ?>
          </div>
          <?php if (!empty($order['courier']) && stripos($order['courier'], 'shadowfax') !== false && !empty($order['tracking_number'])): ?>
            <a href="https://tracker.shadowfax.in/#/track?awb=<?= urlencode($order['tracking_number']) ?>" target="_blank" rel="noopener noreferrer" style="background:#073D26; color:#fff; padding:6px 14px; border-radius:4px; font-size:12px; font-weight:700; text-decoration:none;">
              Track on Shadowfax ↗
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- 2-Column Delivery Details -->
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px; background:#FBF9F5; border:1px solid var(--line); border-radius:8px; padding:16px; margin-bottom:24px;">
        <div>
          <span style="font-size:11px; text-transform:uppercase; letter-spacing:0.08em; color:var(--muted); font-weight:700; display:block; margin-bottom:4px;">Delivery To</span>
          <strong style="color:var(--pine); font-size:14.5px;"><?= htmlspecialchars($order['customer_name'] ?? 'Customer') ?></strong>
          <?php 
            $rawPhone = (string)($order['customer_phone'] ?? '');
            $cleanDigits = preg_replace('/\D/', '', $rawPhone);
            $formattedPhone = (strlen($cleanDigits) === 10) ? '+91 ' . $cleanDigits : (str_starts_with($rawPhone, '+') ? $rawPhone : '+91 ' . $cleanDigits);
          ?>
          <span style="display:block; font-size:12.5px; color:var(--muted); margin-top:2px;">Phone: <?= htmlspecialchars($formattedPhone) ?></span>
        </div>
        <div>
          <span style="font-size:11px; text-transform:uppercase; letter-spacing:0.08em; color:var(--muted); font-weight:700; display:block; margin-bottom:4px;">Shipping Address</span>
          <span style="font-size:13px; color:var(--ink); line-height:1.45;">
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

      <!-- Items Ordered -->
      <h3 style="font-family:var(--serif, serif); font-size:18px; color:var(--pine); margin:0 0 10px 0;">Items Ordered</h3>
      <div style="border-top:1px solid var(--line); margin-bottom:20px;">
        <?php foreach ($order['items'] as $item): ?>
          <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--line); font-size:13.5px;">
            <span><?= htmlspecialchars($item['product_name']) ?> (<?= htmlspecialchars($item['variant_label']) ?> &times; <?= $item['quantity'] ?>)</span>
            <strong><?= Helpers::formatPaise((int)$item['unit_price_paise'] * $item['quantity']) ?></strong>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Order Pricing Summary Card -->
      <div style="background:#F8FBF9; border:1.5px solid #073D26; border-radius:10px; padding:18px 20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid #DFD7CA; padding-bottom:8px; flex-wrap:wrap; gap:8px;">
          <span style="font-size:11.5px; font-weight:800; text-transform:uppercase; letter-spacing:0.08em; color:var(--clay);">
            Order Payment Summary
          </span>
          <span style="font-size:11.5px; font-weight:700; padding:3px 10px; border-radius:10px; background:#e6f4ea; color:#137333;">
            ✓ PAID VIA CASHFREE
          </span>
        </div>

        <div style="display:flex; justify-content:space-between; font-size:14px; padding:4px 0;">
          <span style="color:var(--muted);">Subtotal (<?= count($order['items']) ?> items):</span>
          <strong><?= Helpers::formatPaise((int)($order['subtotal_paise'] ?? 0)) ?></strong>
        </div>

        <?php if (!empty($order['discount_paise'])): ?>
          <div style="display:flex; justify-content:space-between; font-size:14px; padding:4px 0; color:#137333;">
            <span>Discount:</span>
            <strong>-<?= Helpers::formatPaise((int)$order['discount_paise']) ?></strong>
          </div>
        <?php endif; ?>

        <div style="display:flex; justify-content:space-between; font-size:14px; padding:4px 0;">
          <span style="color:var(--muted);">Delivery:</span>
          <strong style="color:<?= ((int)($order['shipping_paise'] ?? 0) === 0) ? '#137333' : 'var(--ink)' ?>;">
            <?= ((int)($order['shipping_paise'] ?? 0) === 0) ? 'FREE' : Helpers::formatPaise((int)$order['shipping_paise']) ?>
          </strong>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; border-top:2px solid #073D26; padding-top:12px; margin-top:8px;">
          <span style="font-size:16px; font-weight:800; color:#073D26;">Total Paid:</span>
          <span style="font-size:22px; font-weight:900; color:#073D26;">
            <?= Helpers::formatPaise((int)($order['total_paise'] ?? ($order['total_amount_paise'] ?? 0))) ?>
          </span>
        </div>
      </div>

      <!-- Quick WhatsApp Help Box -->
      <div style="margin-top:20px; text-align:center;">
        <a
          href="https://wa.me/919059123456?text=<?= urlencode('Hi SVG Dry Fruits, I have a question about my order ' . $order['order_number']) ?>"
          target="_blank"
          rel="noopener noreferrer"
          style="display:inline-flex; align-items:center; gap:8px; background:#25D366; color:#fff; padding:10px 20px; border-radius:6px; font-size:13px; font-weight:700; text-decoration:none; box-shadow:0 2px 8px rgba(37,211,102,0.25);"
        >
          <span>💬</span> Need Help? Chat with us on WhatsApp
        </a>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/layouts/footer.php'; ?>
