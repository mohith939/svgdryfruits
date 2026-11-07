<?php
/**
 * SVG Dry Fruits - Shopping Bag Page
 * Faithfully matches Next.js src/app/cart/page.tsx
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Cart.php';
require_once ROOT_PATH . '/src/Models.php';

$cart = Cart::getDetails();
$pageTitle = 'Your Shopping Bag';
$activePage = 'cart';

$itemCount = $cart['item_count'];
$subtotal = $cart['subtotal_paise'];
$shipping = $cart['shipping_paise'];
$discount = $cart['discount_paise'];
$total = $cart['total_paise'];
$availableCoupons = Models::getCoupons(true);

require __DIR__ . '/layouts/header.php';
?>

<div class="cart-v3-page-wrapper">
  <div class="cart-v3-container">
    <!-- Hero Header -->
    <div class="cart-v3-hero">
      <div class="cart-v3-hero-left">
        <span class="cart-v3-tag">YOUR CART</span>
        <h1 class="cart-v3-title">
          Good Choices<br />
          Go a Long Way
        </h1>
        <p class="cart-v3-subtitle">
          Wholesome dry fruits, closer to a healthier you.<br />
          Review your cart and continue to bring home nature's best.
        </p>
      </div>
      <div class="cart-v3-hero-right">
        <div class="cart-v3-hero-img-box">
          <img src="<?= Helpers::asset('cart-hero-bowl-isolated.png') ?>" alt="Natural dry fruits in artisanal bowl" class="cart-v3-hero-img" />
        </div>
        <div class="cart-v3-hero-script">
          <span class="cart-v3-script-text">
            Real Ingredients.<br />
            Brighter Days.
          </span>
          <span class="cart-v3-script-line"></span>
        </div>
      </div>
    </div>

    <?php if (empty($cart['items'])): ?>
      <!-- Empty Cart State -->
      <div class="cart-v3-empty-card">
        <div class="cart-v3-empty-icon">
          <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5.5 8.5h13l1 12h-15l1-12Z" />
            <path d="M9 9V6a3 3 0 0 1 6 0v3" />
          </svg>
        </div>
        <h2 class="cart-v3-empty-title">Your Cart is Empty</h2>
        <p class="cart-v3-empty-desc">
          Discover our handpicked California almonds, crunchy cashews, roasted pistachios, and pure harvests.
        </p>
        <a href="<?= Helpers::url('shop') ?>" class="cart-v3-empty-btn">
          <span>Explore Our Collection</span>
          <span aria-hidden="true">→</span>
        </a>
      </div>
    <?php else: ?>
      <!-- 2-Column Cart Grid -->
      <div class="cart-v3-grid">
        <!-- Left: Items Card -->
        <div class="cart-v3-items-card">
          <div class="cart-v3-table-head">
            <div class="cart-v3-th-product">PRODUCT</div>
            <div class="cart-v3-th-price">PRICE</div>
            <div class="cart-v3-th-qty">QUANTITY</div>
            <div class="cart-v3-th-total">TOTAL</div>
          </div>

          <div class="cart-v3-table-body">
            <?php foreach ($cart['items'] as $item): 
              $img = !empty($item['image_url']) ? Helpers::asset($item['image_url']) : Helpers::asset('shop-pistachios-hd.jpg');
            ?>
              <div class="cart-v3-item-row">
                <div class="cart-v3-cell-product">
                  <a href="<?= Helpers::url('products/' . $item['product_slug']) ?>" class="cart-v3-thumb-link">
                    <img src="<?= $img ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="cart-v3-thumb-img" />
                  </a>
                  <div class="cart-v3-product-details">
                    <a href="<?= Helpers::url('products/' . $item['product_slug']) ?>" class="cart-v3-product-name">
                      <?= htmlspecialchars($item['product_name']) ?>
                    </a>
                    <span class="cart-v3-variant-label"><?= htmlspecialchars($item['variant_label']) ?></span>
                    <button type="button" onclick="modifyCart('<?= $item['variant_id'] ?>', 0)" class="cart-v3-remove-btn">
                      <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        <line x1="10" y1="11" x2="10" y2="17"></line>
                        <line x1="14" y1="11" x2="14" y2="17"></line>
                      </svg>
                      <span>Remove</span>
                    </button>
                  </div>
                </div>

                <div class="cart-v3-cell-price">
                  <?= Helpers::formatPaise((int)$item['price_paise']) ?>
                </div>

                <div class="cart-v3-cell-qty">
                  <div class="cart-v3-stepper">
                    <button type="button" onclick="modifyCart('<?= $item['variant_id'] ?>', <?= $item['quantity'] - 1 ?>)" class="cart-v3-step-btn">−</button>
                    <span class="cart-v3-step-val"><?= $item['quantity'] ?></span>
                    <button type="button" onclick="modifyCart('<?= $item['variant_id'] ?>', <?= $item['quantity'] + 1 ?>)" class="cart-v3-step-btn">+</button>
                  </div>
                </div>

                <div class="cart-v3-cell-total">
                  <?= Helpers::formatPaise((int)$item['line_total_paise']) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Right: Summary Card -->
        <div class="cart-v3-summary-card">
          <h2 class="cart-v3-summary-title">ORDER SUMMARY</h2>

          <div class="cart-v3-summary-rows">
            <div class="cart-v3-summary-row">
              <span>Subtotal (<?= $itemCount ?> <?= $itemCount === 1 ? 'item' : 'items' ?>)</span>
              <span class="cart-v3-summary-val"><?= Helpers::formatPaise($subtotal) ?></span>
            </div>

            <div class="cart-v3-summary-row">
              <span>Shipping</span>
              <span class="cart-v3-summary-val" style="color: <?= $shipping === 0 ? '#2E7D32' : 'inherit' ?>; font-weight: 600;">
                <?= $shipping === 0 ? 'Free' : Helpers::formatPaise($shipping) ?>
              </span>
            </div>

            <div class="cart-v3-summary-row">
              <span>Estimated Taxes</span>
              <span class="cart-v3-summary-val">₹0</span>
            </div>

            <!-- Available Offers & Coupons -->
            <?php if (!empty($availableCoupons)): ?>
              <div class="cart-coupons-section" style="margin: 12px 0; padding: 14px; background: #FAF8F4; border: 1.5px solid #EAE3D5; border-radius: 10px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                  <span style="font-size: 13px; font-weight: 700; color: #073D26; display: flex; align-items: center; gap: 6px;">
                    🏷️ Available Coupons
                  </span>
                  <span style="font-size: 11px; background: #EAF5EC; color: #073D26; padding: 2px 7px; border-radius: 10px; font-weight: 700;">
                    <?= count($availableCoupons) ?> <?= count($availableCoupons) === 1 ? 'Offer' : 'Offers' ?>
                  </span>
                </div>

                <div class="cart-coupons-list" style="display: flex; flex-direction: column; gap: 8px;">
                  <?php foreach ($availableCoupons as $cpn): 
                    $cpnCode = strtoupper($cpn['code']);
                    $isApplied = (!empty($cart['applied_coupon']) && strtoupper($cart['applied_coupon']['code']) === $cpnCode);
                    $minPaise = (int)$cpn['min_subtotal_paise'];
                    $isApplicable = ($subtotal >= $minPaise);
                    $diffPaise = max(0, $minPaise - $subtotal);

                    $discountLabel = '';
                    if ($cpn['type'] === 'percentage') {
                        $discountLabel = $cpn['value'] . '% OFF';
                    } elseif ($cpn['type'] === 'free_shipping') {
                        $discountLabel = 'FREE SHIPPING';
                    } else {
                        $discountLabel = Helpers::formatPaise((int)$cpn['value']) . ' OFF';
                    }
                  ?>
                    <div class="cart-coupon-card <?= $isApplied ? 'applied' : ($isApplicable ? '' : 'locked') ?>" 
                         style="background: #fff; border: 1.5px dashed <?= $isApplied ? '#2E7D32' : ($isApplicable ? '#073D26' : '#C7BFB1') ?>; border-radius: 8px; padding: 10px 12px; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                      <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 3px; flex-wrap: wrap;">
                          <span style="font-family: monospace; font-size: 12.5px; font-weight: 700; color: #073D26; background: #F4EFE6; padding: 2px 7px; border-radius: 4px; letter-spacing: 0.5px;">
                            <?= htmlspecialchars($cpnCode) ?>
                          </span>
                          <span style="font-size: 11px; font-weight: 700; color: #2E7D32; background: #EAF5EC; padding: 2px 6px; border-radius: 4px;">
                            <?= $discountLabel ?>
                          </span>
                        </div>
                        <div style="font-size: 12px; font-weight: 600; color: #2A362D; line-height: 1.3;">
                          <?= htmlspecialchars($cpn['name'] ?? $cpnCode) ?>
                        </div>
                        <?php if (!empty($cpn['description'])): ?>
                          <div style="font-size: 11px; color: #6E6454; line-height: 1.3; margin-top: 2px;">
                            <?= htmlspecialchars($cpn['description']) ?>
                          </div>
                        <?php endif; ?>
                        <div style="font-size: 10.5px; color: #8E6D38; font-weight: 600; margin-top: 3px;">
                          <?= $minPaise > 0 ? 'Min order: ' . Helpers::formatPaise($minPaise) : 'No minimum order required' ?>
                        </div>
                      </div>

                      <div style="flex-shrink: 0; text-align: right;">
                        <?php if ($isApplied): ?>
                          <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 2px;">
                            <span style="font-size: 11px; font-weight: 700; color: #2E7D32; background: #EAF5EC; padding: 3px 8px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px;">
                              ✓ Applied
                            </span>
                            <button type="button" onclick="removeCouponCode()" style="background: none; border: none; color: #9B1C1C; font-size: 11px; text-decoration: underline; cursor: pointer; padding: 2px 0;">
                              Remove
                            </button>
                          </div>
                        <?php elseif ($isApplicable): ?>
                          <button type="button" onclick="applyDirectCoupon('<?= htmlspecialchars($cpnCode, ENT_QUOTES) ?>')" class="cart-coupon-apply-btn" style="background: #073D26; color: #fff; border: none; border-radius: 6px; padding: 6px 14px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s;">
                            Apply
                          </button>
                        <?php else: ?>
                          <span style="display: inline-block; font-size: 10.5px; color: #A35A00; background: #FFF8E6; border: 1px solid #FFE299; padding: 3px 7px; border-radius: 5px; font-weight: 600; text-align: right; line-height: 1.2;">
                            Add <?= Helpers::formatPaise($diffPaise) ?><br />to unlock
                          </span>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

            <!-- Promo Box -->
            <div class="cart-v3-summary-promo-box">
              <?php if (!empty($cart['applied_coupon'])): ?>
                <div class="cart-v3-summary-applied-coupon">
                  <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="font-size: 13px; color: #2E7D32; font-weight: 600;">🏷️ <?= htmlspecialchars($cart['applied_coupon']['code']) ?></span>
                    <span style="font-size: 11.5px; color: #2E7D32;">(Applied)</span>
                  </div>
                  <button type="button" onclick="removeCouponCode()" class="cart-v3-promo-remove-btn">Remove</button>
                </div>
              <?php else: ?>
                <form id="cart-promo-form" onsubmit="applyCouponCode(event)" class="cart-v3-summary-promo-form">
                  <input type="text" id="cart-promo-input" placeholder="Or enter coupon code" class="cart-v3-summary-promo-input" />
                  <button type="submit" class="cart-v3-summary-promo-btn">Apply</button>
                </form>
                <div id="cart-promo-err" style="color: #9B1C1C; font-size: 11.5px; margin-top: 4px; display: none;"></div>
              <?php endif; ?>
            </div>

            <?php if ($discount > 0): ?>
              <div class="cart-v3-summary-row" style="color: #2E7D32;">
                <span>Coupon Discount</span>
                <span class="cart-v3-summary-val">−<?= Helpers::formatPaise($discount) ?></span>
              </div>
            <?php endif; ?>

            <div class="cart-v3-summary-row cart-v3-summary-total">
              <span>Total</span>
              <span class="cart-v3-summary-val"><?= Helpers::formatPaise($total) ?></span>
            </div>
          </div>

          <a href="<?= Helpers::url('checkout') ?>" class="cart-v3-checkout-btn">
            Proceed to Checkout →
          </a>

          <div style="text-align: center; margin-top: 14px;">
            <a href="<?= Helpers::url('shop') ?>" style="font-size: 12.5px; color: #6B6255; text-decoration: underline;">
              Continue Shopping
            </a>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Features Banner -->
    <div class="cart-v3-features-banner">
      <div class="cart-v3-feature-item">
        <div class="cart-v3-feat-icon-circle">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#2A382C" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"></path>
            <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"></path>
          </svg>
        </div>
        <div class="cart-v3-feat-info">
          <span class="cart-v3-feat-title">Premium Quality</span>
          <span class="cart-v3-feat-desc">Sourced from trusted farms</span>
        </div>
      </div>

      <div class="cart-v3-feature-item">
        <div class="cart-v3-feat-icon-circle">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#2A382C" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path>
            <path d="m3.3 7 8.7 5 8.7-5"></path>
            <path d="M12 22V12"></path>
          </svg>
        </div>
        <div class="cart-v3-feat-info">
          <span class="cart-v3-feat-title">Safe Packaging</span>
          <span class="cart-v3-feat-desc">Freshness in every pack</span>
        </div>
      </div>

      <div class="cart-v3-feature-item">
        <div class="cart-v3-feat-icon-circle">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#2A382C" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <rect x="1" y="3" width="15" height="13"></rect>
            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
            <circle cx="5.5" cy="18.5" r="2.5"></circle>
            <circle cx="18.5" cy="18.5" r="2.5"></circle>
          </svg>
        </div>
        <div class="cart-v3-feat-info">
          <span class="cart-v3-feat-title">Fast Delivery</span>
          <span class="cart-v3-feat-desc">Pan India</span>
        </div>
      </div>

      <div class="cart-v3-feat-script">
        <span class="cart-v3-feat-script-text">Nourishment for a Brighter You</span>
        <span class="cart-v3-feat-script-line"></span>
      </div>
    </div>
  </div>
</div>

<script>
function modifyCart(variantId, qty) {
  fetch(window.SVG_APP.apiUrl + '/cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'update', variant_id: variantId, quantity: qty })
  })
    .then(function(r) { return r.json(); })
    .then(function(res) {
      if (res.success) {
        window.location.reload();
      }
    });
}

function applyDirectCoupon(code) {
  if (!code) return;
  var errEl = document.getElementById('cart-promo-err');
  if (errEl) errEl.style.display = 'none';

  fetch(window.SVG_APP.apiUrl + '/cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'apply_coupon', code: code })
  })
    .then(function(r) { return r.json(); })
    .then(function(res) {
      if (res.success) {
        window.location.reload();
      } else {
        alert(res.error || 'Failed to apply coupon.');
      }
    })
    .catch(function(err) {
      console.error(err);
      alert('Unable to connect to server. Please try again.');
    });
}

function applyCouponCode(e) {
  e.preventDefault();
  var input = document.getElementById('cart-promo-input');
  var code = input ? input.value.trim() : '';
  var errEl = document.getElementById('cart-promo-err');
  if (!code) return;

  fetch(window.SVG_APP.apiUrl + '/cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'apply_coupon', code: code })
  })
    .then(function(r) { return r.json(); })
    .then(function(res) {
      if (res.success) {
        window.location.reload();
      } else {
        if (errEl) {
          errEl.textContent = res.error || 'Invalid promo code';
          errEl.style.display = 'block';
        }
      }
    });
}

function removeCouponCode() {
  fetch(window.SVG_APP.apiUrl + '/cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'remove_coupon' })
  })
    .then(function() {
      window.location.reload();
    });
}
</script>

<?php require __DIR__ . '/layouts/footer.php'; ?>
