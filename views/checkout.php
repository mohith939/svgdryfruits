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

      <!-- Step 3: Multi-Option Delivery Methods -->
      <?php 
        $eligibleShipping = $cart['eligible_shipping'] ?? [];
        $selectedShipping = $cart['selected_shipping'] ?? null;
      ?>
      <div class="checkout-section-box shipping-method-section-box" style="margin-bottom: 24px;">
        <h2>3. Delivery method</h2>
        <p style="font-size: 13.5px; color: var(--muted); margin-bottom: 14px;">
          Choose your preferred courier speed and delivery option:
        </p>

        <div class="shipping-options-list" style="display: flex; flex-direction: column; gap: 10px;">
          <?php foreach ($eligibleShipping as $idx => $opt): 
            $isSelected = ($selectedShipping && $selectedShipping['id'] === $opt['id']);
            $feePaise = (int)$opt['effective_paise'];
            $feeLabel = ($feePaise === 0) ? 'FREE' : Helpers::formatPaise($feePaise);
          ?>
            <label
              class="shipping-option-card <?= $isSelected ? 'is-selected' : '' ?>"
              id="ship_card_<?= $opt['id'] ?>"
              style="display: flex; align-items: flex-start; gap: 14px; padding: 16px; border: 2px solid <?= $isSelected ? '#073D26' : '#DFD7CA' ?>; border-radius: 10px; cursor: pointer; background: <?= $isSelected ? '#F8FBF9' : '#fff' ?>; transition: all 0.2s;"
            >
              <input
                type="radio"
                name="shipping_option_id"
                value="<?= htmlspecialchars($opt['id']) ?>"
                data-rate="<?= $feePaise ?>"
                data-name="<?= htmlspecialchars($opt['name']) ?>"
                <?= $isSelected ? 'checked' : '' ?>
                onchange="selectShippingOption(this)"
                style="margin-top: 4px; accent-color: #073D26; width: 18px; height: 18px;"
              >
              <div style="flex: 1;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                  <strong style="color: #073D26; font-size: 15.5px;"><?= htmlspecialchars($opt['name']) ?></strong>
                  <span style="font-weight: 700; font-size: 15px; color: <?= ($feePaise === 0) ? '#2E7D32' : '#073D26' ?>;">
                    <?= $feeLabel ?>
                  </span>
                </div>
                <?php if (!empty($opt['estimated_days'])): ?>
                  <span style="display: inline-block; font-size: 11px; padding: 2px 8px; border-radius: 4px; background: #F4EFE6; color: #5D4F3C; font-weight: 600; margin-bottom: 4px;">
                    ⏱ Estimated: <?= htmlspecialchars($opt['estimated_days']) ?>
                  </span>
                <?php endif; ?>
                <?php if (!empty($opt['description'])): ?>
                  <p style="margin: 3px 0 0; font-size: 13px; color: #666; line-height: 1.4;">
                    <?= htmlspecialchars($opt['description']) ?>
                  </p>
                <?php endif; ?>
                <?php if (!empty($opt['free_above_paise']) && $subtotal < $opt['free_above_paise']): ?>
                  <p style="margin: 4px 0 0 0; font-size: 12px; color: #8E6D38; font-weight: 600;">
                    Add <?= Helpers::formatPaise($opt['free_above_paise'] - $subtotal) ?> more for FREE shipping on this option!
                  </p>
                <?php endif; ?>
              </div>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="checkout-section-box payment-section-box">
        <h2>4. Payment method</h2>
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
          <dd id="summary-shipping-val"><?= $shipping === 0 ? 'Free' : Helpers::formatPaise($shipping) ?></dd>
        </div>
        <div class="checkout-grand-total">
          <dt>Total payable</dt>
          <dd id="summary-total-val"><?= Helpers::formatPaise($total) ?></dd>
        </div>
      </dl>

      <div class="checkout-back-link">
        <a href="<?= Helpers::url('cart') ?>">← Edit bag or coupon</a>
      </div>
    </aside>
  </div>
</section>

<script>
var baseSubtotalPaise = <?= (int)$subtotal ?>;
var discountPaise = <?= (int)$discount ?>;
var currentShippingPaise = <?= (int)$shipping ?>;

function formatINR(paise) {
  var rupees = paise / 100;
  return '₹' + rupees.toLocaleString('en-IN', { maximumFractionDigits: (paise % 100 === 0) ? 0 : 2 });
}

function selectShippingOption(radio) {
  var rate = parseInt(radio.getAttribute('data-rate'), 10) || 0;
  var name = radio.getAttribute('data-name') || '';
  currentShippingPaise = rate;

  document.querySelectorAll('.shipping-option-card').forEach(function(card) {
    card.style.borderColor = '#DFD7CA';
    card.style.backgroundColor = '#fff';
  });
  var parent = radio.closest('.shipping-option-card');
  if (parent) {
    parent.style.borderColor = '#073D26';
    parent.style.backgroundColor = '#F8FBF9';
  }

  var shippingEl = document.getElementById('summary-shipping-val');
  if (shippingEl) {
    shippingEl.textContent = (rate === 0) ? 'Free' : formatINR(rate);
  }

  var newTotalPaise = Math.max(0, baseSubtotalPaise - discountPaise + rate);
  var totalEl = document.getElementById('summary-total-val');
  if (totalEl) {
    totalEl.textContent = formatINR(newTotalPaise);
  }

  var submitBtn = document.getElementById('checkout-submit-btn');
  if (submitBtn) {
    submitBtn.textContent = 'Pay ' + formatINR(newTotalPaise) + ' with Cashfree';
  }
}

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

  var selectedShippingRadio = document.querySelector('input[name="shipping_option_id"]:checked');
  var shippingOptId = selectedShippingRadio ? selectedShippingRadio.value : '';
  var shippingOptName = selectedShippingRadio ? selectedShippingRadio.getAttribute('data-name') : '';

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
    shipping_pincode: document.getElementById('pincode').value.trim(),
    shipping_option_id: shippingOptId,
    shipping_option_name: shippingOptName
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
