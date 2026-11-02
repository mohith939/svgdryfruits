<?php
/**
 * SVG Dry Fruits - Guest Checkout Page
 * Faithfully matches Next.js src/app/checkout/page.tsx
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Cart.php';
require_once ROOT_PATH . '/src/Icon.php';

$cart = Cart::getDetails();
if (empty($cart['items'])) {
    Helpers::redirect(Helpers::url('cart'));
}

$pageTitle = 'Guest Checkout';
$activePage = 'checkout';

$subtotal = $cart['subtotal_paise'];
$shipping = $cart['shipping_paise'];
$discount = $cart['discount_paise'];
$total = $cart['total_paise'];
$coupon = $cart['applied_coupon']['code'] ?? '';

require __DIR__ . '/layouts/header.php';
?>

<section class="checkout-page">
  <div class="checkout-head">
    <div class="breadcrumbs">
      <a href="<?= Helpers::url('/') ?>">Home</a>
      <span aria-hidden="true">/</span>
      <a href="<?= Helpers::url('cart') ?>">Bag</a>
      <span aria-hidden="true">/</span>
      <span>Checkout</span>
    </div>
    <h1>Guest <em>checkout.</em></h1>
    <p class="checkout-kicker">No account needed. Enter your delivery address to proceed with secure payment.</p>
  </div>

  <div id="checkout-err-banner" class="checkout-error-banner" role="alert" style="display:none;">
    <?= Icon::render('shield', ['size' => 20]) ?>
    <p id="checkout-err-msg"></p>
  </div>

  <div class="checkout-grid">
    <!-- Left column: Guest customer and shipping form -->
    <form class="checkout-form" id="checkout-form" onsubmit="handleCheckoutSubmit(event)">
      <div class="checkout-section-box">
        <h2>1. Contact details</h2>
        <div class="form-group-row">
          <div class="form-group">
            <label for="name">Full name <span class="req">*</span></label>
            <input id="name" name="name" required placeholder="e.g. Ramesh Kumar" autocomplete="name" />
          </div>
          <div class="form-group">
            <label for="phone">Mobile number <span class="req">*</span></label>
            <div class="phone-input-wrap">
              <span class="phone-prefix">+91</span>
              <input id="phone" name="phone" type="tel" required placeholder="9876543210" maxlength="10" pattern="[6-9][0-9]{9}" autocomplete="tel" />
            </div>
          </div>
        </div>
        <div class="form-group">
          <label for="email">Email address <span class="opt">(optional)</span></label>
          <input id="email" name="email" type="email" placeholder="ramesh@example.com" autocomplete="email" />
          <span class="field-hint">We'll send order tracking updates if provided.</span>
        </div>
      </div>

      <div class="checkout-section-box">
        <h2>2. Delivery address</h2>
        <div class="form-group">
          <label for="street">Street address / Flat / House no <span class="req">*</span></label>
          <input id="street" name="street" required placeholder="Flat 402, Lotus Residency, MG Road" autocomplete="street-address" />
        </div>

        <div class="form-group">
          <label for="landmark">Landmark <span class="opt">(optional)</span></label>
          <input id="landmark" name="landmark" placeholder="Near City Hospital" />
        </div>

        <div class="form-group-row three-col">
          <div class="form-group">
            <label for="pincode">PIN code <span class="req">*</span></label>
            <input id="pincode" name="pincode" required maxlength="6" pattern="[0-9]{6}" placeholder="500001" autocomplete="postal-code" />
          </div>
          <div class="form-group">
            <label for="city">City / Town <span class="req">*</span></label>
            <input id="city" name="city" required placeholder="Hyderabad" autocomplete="address-level2" />
          </div>
          <div class="form-group">
            <label for="state">State <span class="req">*</span></label>
            <input id="state" name="state" required placeholder="Telangana" autocomplete="address-level1" />
          </div>
        </div>
      </div>

      <div class="checkout-section-box payment-section-box">
        <h2>3. Payment method</h2>
        <div class="payment-method-card">
          <div class="payment-method-header">
            <?= Icon::render('card', ['size' => 24]) ?>
            <strong>Cashfree Secure Online Payment</strong>
          </div>
          <p>Pay securely using UPI (Google Pay, PhonePe, Paytm), NetBanking, Credit or Debit Cards. Cash on delivery is not offered.</p>
        </div>

        <button type="submit" id="checkout-submit-btn" class="button button-primary checkout-submit-btn">
          Pay <?= Helpers::formatPaise($total) ?> with Cashfree
        </button>

        <p class="checkout-guarantee">
          <?= Icon::render('shield', ['size' => 16]) ?> 256-bit encrypted gateway. Your card details are never stored on our server.
        </p>
      </div>
    </form>

    <!-- Right column: Order Summary -->
    <aside class="checkout-summary" aria-label="Items in this order">
      <h2>Order summary</h2>
      <ul class="checkout-item-list">
        <?php foreach ($cart['items'] as $item): 
          $img = !empty($item['image_url']) ? Helpers::asset($item['image_url']) : Helpers::asset('mixed-nuts-reference.jpg');
        ?>
          <li class="checkout-item-row">
            <div class="checkout-item-thumb">
              <img src="<?= $img ?>" alt="" />
              <span class="checkout-item-badge"><?= $item['quantity'] ?></span>
            </div>
            <div class="checkout-item-meta">
              <strong><?= htmlspecialchars($item['product_name']) ?></strong>
              <span><?= htmlspecialchars($item['variant_label']) ?></span>
            </div>
            <div class="checkout-item-price">
              <?= Helpers::formatPaise((int)$item['line_total_paise']) ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>

      <dl class="checkout-totals">
        <div>
          <dt>Subtotal</dt>
          <dd><?= Helpers::formatPaise($subtotal) ?></dd>
        </div>
        <?php if ($discount > 0): ?>
          <div class="discount-row">
            <dt>Discount (<?= htmlspecialchars($coupon) ?>)</dt>
            <dd>−<?= Helpers::formatPaise($discount) ?></dd>
          </div>
        <?php endif; ?>
        <div>
          <dt>Shipping</dt>
          <dd><?= $shipping === 0 ? 'Free' : Helpers::formatPaise($shipping) ?></dd>
        </div>
        <div class="checkout-grand-total">
          <dt>Total payable</dt>
          <dd><?= Helpers::formatPaise($total) ?></dd>
        </div>
      </dl>

      <div class="checkout-back-link">
        <a href="<?= Helpers::url('cart') ?>">← Edit bag or coupon</a>
      </div>
    </aside>
  </div>
</section>

<script>
function handleCheckoutSubmit(e) {
  e.preventDefault();
  var btn = document.getElementById('checkout-submit-btn');
  var errBanner = document.getElementById('checkout-err-banner');
  var errMsg = document.getElementById('checkout-err-msg');

  if (errBanner) errBanner.style.display = 'none';
  if (btn) {
    btn.disabled = true;
    btn.textContent = 'Redirecting to Cashfree…';
  }

  var payload = {
    name: document.getElementById('name').value.trim(),
    customer_name: document.getElementById('name').value.trim(),
    phone: document.getElementById('phone').value.trim(),
    customer_phone: document.getElementById('phone').value.trim(),
    email: document.getElementById('email').value.trim(),
    customer_email: document.getElementById('email').value.trim(),
    street: document.getElementById('street').value.trim(),
    shipping_street: document.getElementById('street').value.trim(),
    landmark: document.getElementById('landmark').value.trim(),
    shipping_landmark: document.getElementById('landmark').value.trim(),
    city: document.getElementById('city').value.trim(),
    shipping_city: document.getElementById('city').value.trim(),
    state: document.getElementById('state').value.trim(),
    shipping_state: document.getElementById('state').value.trim(),
    pincode: document.getElementById('pincode').value.trim(),
    shipping_pincode: document.getElementById('pincode').value.trim()
  };


  fetch(window.SVG_APP.apiUrl + '/checkout.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
    .then(async function(r) {
      const text = await r.text();
      let res;
      try {
        res = JSON.parse(text);
      } catch (e) {
        throw new Error('Server returned an unexpected response: ' + (text.replace(/<[^>]*>?/gm, '').trim().slice(0, 120) || text.slice(0, 120)));
      }
      return res;
    })
    .then(function(res) {
      if (!res.success) {
        throw new Error(res.error || 'Failed to initialize payment.');
      }

      if (!res.payment_session_id) {
        throw new Error('Cashfree did not return a payment session ID. Please try again.');
      }

      // Check if Cashfree Drop-in SDK is available
      if (typeof window.Cashfree === 'function') {
        var mode = (window.SVG_APP && window.SVG_APP.cashfreeMode) ? window.SVG_APP.cashfreeMode : 'production';
        var cashfree = window.Cashfree({ mode: mode });

        cashfree.checkout({
          paymentSessionId: res.payment_session_id,
          redirectTarget: '_modal'
        }).then(function(result) {
          if (result && result.error) {
            console.warn('Cashfree payment error or closed:', result.error);
            if (errMsg) errMsg.textContent = result.error.message || 'Payment was not completed. You can try again.';
            if (errBanner) errBanner.style.display = 'flex';
            if (btn) {
              btn.disabled = false;
              btn.textContent = 'Pay <?= Helpers::formatPaise($total) ?> with Cashfree';
            }
            return;
          }
          if (result && (result.paymentDetails || result.redirect)) {
            // Payment successful! Redirect to order confirmed screen
            window.location.href = res.redirect_url;
          }
        }).catch(function(cfErr) {
          console.error('Cashfree SDK checkout exception:', cfErr);
          // Fallback to _self redirect if modal is blocked
          if (typeof cashfree.checkout === 'function') {
            cashfree.checkout({
              paymentSessionId: res.payment_session_id,
              redirectTarget: '_self'
            });
          }
        });
      } else {
        throw new Error('Cashfree Payment Gateway SDK failed to load. Please check your internet connection and refresh.');
      }
    })
    .catch(function(err) {
      console.error(err);
      if (errMsg) errMsg.textContent = err.message || 'Payment initiation failed.';
      if (errBanner) errBanner.style.display = 'flex';
      if (btn) {
        btn.disabled = false;
        btn.textContent = 'Pay <?= Helpers::formatPaise($total) ?> with Cashfree';
      }
    });
}
</script>

<?php require __DIR__ . '/layouts/footer.php'; ?>
