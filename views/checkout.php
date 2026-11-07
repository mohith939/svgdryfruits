<?php
/**
 * SVG Dry Fruits - Guest Checkout Page
 * Modern, luxury dry-fruits e-commerce checkout with multi-tier location auto-fetch
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Cart.php';
require_once ROOT_PATH . '/src/Icon.php';

$cart = Cart::getDetails();
if (empty($cart['items'])) {
    Helpers::redirect(Helpers::url('cart'));
}

$pageTitle = 'Secure Checkout';
$activePage = 'checkout';

$subtotal = $cart['subtotal_paise'];
$shipping = $cart['shipping_paise'];
$discount = $cart['discount_paise'];
$total = $cart['total_paise'];
$coupon = $cart['applied_coupon']['code'] ?? '';

require __DIR__ . '/layouts/header.php';
?>

<div class="checkout-page-modern">
  <!-- Header & Breadcrumbs -->
  <div class="checkout-header-modern">
    <div class="checkout-breadcrumbs">
      <a href="<?= Helpers::url('/') ?>">Home</a>
      <span aria-hidden="true">›</span>
      <a href="<?= Helpers::url('cart') ?>">Shopping Bag</a>
      <span aria-hidden="true">›</span>
      <span style="color: #073D26; font-weight: 600;">Checkout</span>
    </div>

    <div class="checkout-title-wrap">
      <h1 class="checkout-title-main">Complete Your Order</h1>
      <p class="checkout-title-kicker">
        Direct dispatch from Hyderabad &amp; Kakinada • Freshness Sealed • Pan-India Delivery
      </p>
    </div>

    <!-- Stepper indicator -->
    <div class="checkout-step-pill-bar">
      <span class="checkout-step-pill active">
        <span style="width:6px;height:6px;border-radius:50%;background:#4ADE80;"></span>
        01. Contact
      </span>
      <span class="checkout-step-pill active">
        <span style="width:6px;height:6px;border-radius:50%;background:#4ADE80;"></span>
        02. Delivery Address
      </span>
      <span class="checkout-step-pill active">
        <span style="width:6px;height:6px;border-radius:50%;background:#4ADE80;"></span>
        03. Shipping Rate
      </span>
      <span class="checkout-step-pill active">
        <span style="width:6px;height:6px;border-radius:50%;background:#4ADE80;"></span>
        04. Cashfree Payment
      </span>
    </div>
  </div>

  <!-- Global Error Banner -->
  <div id="checkout-err-banner" class="checkout-error-banner" role="alert" style="display:none; border-radius: 10px; margin-bottom: 24px; padding: 14px 18px;">
    <?= Icon::render('shield', ['size' => 20]) ?>
    <p id="checkout-err-msg" style="margin: 0; font-weight: 500;"></p>
  </div>

  <div class="checkout-grid" style="align-items: start;">
    <!-- Left Column: Checkout Multi-Step Form -->
    <form class="checkout-form" id="checkout-form" onsubmit="handleCheckoutSubmit(event)">
      
      <!-- STEP 1: CONTACT DETAILS -->
      <div class="checkout-card-box">
        <div class="checkout-card-header">
          <div class="checkout-card-heading-group">
            <span class="checkout-card-number-badge">01</span>
            <h2 class="checkout-card-title">Contact Information</h2>
          </div>
          <span style="font-size: 12px; color: #7B7265; background: #FAF7F2; padding: 3px 8px; border-radius: 6px;">
            Guest Checkout
          </span>
        </div>

        <div class="form-group-row">
          <div class="form-group">
            <label for="name" style="font-weight: 600; color: #2A362D;">
              Full Name <span class="req" style="color: #9B1C1C;">*</span>
            </label>
            <input 
              id="name" 
              name="name" 
              class="checkout-input-modern" 
              required 
              placeholder="e.g. Ramesh Kumar" 
              autocomplete="name" 
            />
          </div>

          <div class="form-group">
            <label for="phone" style="font-weight: 600; color: #2A362D;">
              Mobile Number (for delivery SMS) <span class="req" style="color: #9B1C1C;">*</span>
            </label>
            <div class="phone-input-wrap-modern">
              <span class="phone-prefix-modern">🇮🇳 +91</span>
              <input 
                id="phone" 
                name="phone" 
                class="phone-input-modern" 
                type="tel" 
                required 
                placeholder="9876543210" 
                maxlength="10" 
                pattern="[6-9][0-9]{9}" 
                autocomplete="tel" 
              />
            </div>
          </div>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label for="email" style="font-weight: 600; color: #2A362D;">
            Email Address <span class="opt" style="font-size: 11.5px; color: #7B7265; font-weight: normal;">(optional)</span>
          </label>
          <input 
            id="email" 
            name="email" 
            type="email" 
            class="checkout-input-modern" 
            placeholder="ramesh@example.com" 
            autocomplete="email" 
          />
          <span class="field-hint" style="font-size: 12px; color: #7B7265; margin-top: 5px;">
            We will email order confirmation and Cashfree payment receipt.
          </span>
        </div>
      </div>

      <!-- STEP 2: DELIVERY ADDRESS -->
      <div class="checkout-card-box">
        <div class="checkout-card-header">
          <div class="checkout-card-heading-group">
            <span class="checkout-card-number-badge">02</span>
            <h2 class="checkout-card-title">Delivery Address</h2>
          </div>

          <!-- Bulletproof Location Auto-Detect Button -->
          <button
            type="button"
            id="btn-use-location"
            class="btn-detect-location-modern"
            onclick="autoFetchCurrentLocation()"
            title="Automatically detect address, PIN code & state"
          >
            <span class="location-radar-dot"></span>
            <span id="btn-use-location-text">Auto-Detect My Location</span>
          </button>
        </div>

        <!-- Location Status Alert -->
        <div id="location-fetch-status" class="location-status-bar"></div>

        <div class="form-group">
          <label for="street" style="font-weight: 600; color: #2A362D;">
            Flat / House No / Building / Street <span class="req" style="color: #9B1C1C;">*</span>
          </label>
          <input 
            id="street" 
            name="street" 
            class="checkout-input-modern" 
            required 
            placeholder="e.g. Flat 402, Lotus Residency, Road No. 12" 
            autocomplete="street-address" 
          />
        </div>

        <div class="form-group">
          <label for="landmark" style="font-weight: 600; color: #2A362D;">
            Landmark <span class="opt" style="font-size: 11.5px; color: #7B7265; font-weight: normal;">(optional)</span>
          </label>
          <input 
            id="landmark" 
            name="landmark" 
            class="checkout-input-modern" 
            placeholder="e.g. Near City Center Mall / Apollo Pharmacy" 
          />
        </div>

        <div class="form-group-row three-col">
          <div class="form-group">
            <label for="pincode" style="font-weight: 600; color: #2A362D;">
              PIN Code <span class="req" style="color: #9B1C1C;">*</span>
            </label>
            <input 
              id="pincode" 
              name="pincode" 
              class="checkout-input-modern" 
              required 
              maxlength="6" 
              pattern="[0-9]{6}" 
              placeholder="500001" 
              autocomplete="postal-code" 
              oninput="handlePincodeInput(this.value)"
            />
            <div id="pin-status-pill" class="pin-status-pill"></div>
          </div>

          <div class="form-group">
            <label for="city" style="font-weight: 600; color: #2A362D;">
              City / District <span class="req" style="color: #9B1C1C;">*</span>
            </label>
            <input 
              id="city" 
              name="city" 
              class="checkout-input-modern" 
              required 
              placeholder="Hyderabad" 
              autocomplete="address-level2" 
            />
          </div>

          <div class="form-group">
            <label for="state" style="font-weight: 600; color: #2A362D;">
              State <span class="req" style="color: #9B1C1C;">*</span>
            </label>
            <select 
              id="state" 
              name="state" 
              class="checkout-input-modern" 
              required 
              onchange="handleStateChange(this.value)" 
              style="padding: 0 12px; cursor: pointer;"
            >
              <option value="Telangana" selected>Telangana</option>
              <option value="Andhra Pradesh">Andhra Pradesh</option>
              <option value="Karnataka">Karnataka</option>
              <option value="Tamil Nadu">Tamil Nadu</option>
              <option value="Kerala">Kerala</option>
              <option value="Maharashtra">Maharashtra</option>
              <option value="Delhi">Delhi / NCR</option>
              <option value="Gujarat">Gujarat</option>
              <option value="Rajasthan">Rajasthan</option>
              <option value="Uttar Pradesh">Uttar Pradesh</option>
              <option value="West Bengal">West Bengal</option>
              <option value="Madhya Pradesh">Madhya Pradesh</option>
              <option value="Punjab">Punjab</option>
              <option value="Haryana">Haryana</option>
              <option value="Bihar">Bihar</option>
              <option value="Odisha">Odisha</option>
              <option value="Assam">Assam</option>
              <option value="Goa">Goa</option>
              <option value="Jharkhand">Jharkhand</option>
              <option value="Chhattisgarh">Chhattisgarh</option>
              <option value="Uttarakhand">Uttarakhand</option>
              <option value="Himachal Pradesh">Himachal Pradesh</option>
              <option value="Jammu and Kashmir">Jammu and Kashmir</option>
              <option value="Chandigarh">Chandigarh</option>
              <option value="Puducherry">Puducherry</option>
              <option value="Other">Other / Rest of India</option>
            </select>
          </div>
        </div>
      </div>

      <!-- STEP 3: AUTOMATED WEIGHT & STATE-BASED SHIPPING RATE -->
      <div class="checkout-card-box">
        <div class="checkout-card-header">
          <div class="checkout-card-heading-group">
            <span class="checkout-card-number-badge">03</span>
            <h2 class="checkout-card-title">Shipping &amp; Delivery Option</h2>
          </div>
          <span style="font-size: 12px; color: #073D26; background: #EAF5EC; padding: 3px 8px; border-radius: 6px; font-weight: 700;">
            Auto-Calculated
          </span>
        </div>

        <div id="shipping-rate-card" style="padding: 18px 20px; border: 2px solid #073D26; border-radius: 12px; background: #F8FBF9; display: flex; align-items: flex-start; gap: 14px;">
          <div style="font-size: 28px; line-height: 1; padding-top: 2px;">🚚</div>
          <div style="flex: 1;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; flex-wrap: wrap; gap: 8px;">
              <strong id="shipping-rule-name" style="color: #073D26; font-size: 16px;">
                Direct Delivery to <span id="shipping-rule-state">Telangana</span>
              </strong>
              <span id="shipping-rate-badge" style="font-weight: 700; font-size: 15px; color: #073D26; background: #EAF5EC; padding: 5px 12px; border-radius: 6px;">
                <?= $shipping === 0 ? 'FREE DELIVERY' : Helpers::formatPaise($shipping) ?>
              </span>
            </div>

            <div style="margin: 6px 0 10px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
              <span id="shipping-weight-badge" style="font-size: 12px; background: #FAF7F2; border: 1px solid #DFD7CA; color: #5D4F3C; padding: 3px 9px; border-radius: 5px; font-weight: 600;">
                ⚖️ Bag Weight: <?= $cart['total_weight_kg'] ?? 0.5 ?> kg (<?= $cart['total_weight_grams'] ?? 500 ?>g)
              </span>
              <span id="shipping-tier-badge" style="font-size: 12px; background: #FAF7F2; border: 1px solid #DFD7CA; color: #073D26; padding: 3px 9px; border-radius: 5px; font-weight: 700;">
                Tier: Below 1 kg
              </span>
            </div>

            <p id="shipping-rule-desc" style="margin: 4px 0 8px; font-size: 13.5px; color: #4B4336; line-height: 1.5;">
              Dispatched with food-grade protective packing from our Hyderabad &amp; Kakinada facilities. Shipping dynamically calculates based on destination &amp; parcel weight.
            </p>

            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
              <span id="shipping-rule-sla" style="display: inline-block; font-size: 12px; padding: 3px 9px; border-radius: 5px; background: #F4EFE6; color: #5D4F3C; font-weight: 600;">
                ⏱ Estimated: 1 – 3 Business Days
              </span>
              <span id="shipping-free-hint" style="font-size: 12px; color: #8E6D38; font-weight: 600;"></span>
            </div>
          </div>
        </div>
      </div>

      <!-- STEP 4: CASHFREE PAYMENT METHOD -->
      <div class="checkout-card-box">
        <div class="checkout-card-header">
          <div class="checkout-card-heading-group">
            <span class="checkout-card-number-badge">04</span>
            <h2 class="checkout-card-title">Payment Method</h2>
          </div>
          <span style="font-size: 12px; color: #2E7D32; background: #EAF5EC; padding: 3px 8px; border-radius: 6px; font-weight: 700;">
            100% Encrypted
          </span>
        </div>

        <div style="padding: 18px 20px; border: 1.5px solid #073D26; border-radius: 12px; background: #F7FAF8; margin-bottom: 22px;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
            <div style="display: flex; align-items: center; gap: 10px;">
              <?= Icon::render('card', ['size' => 24]) ?>
              <strong style="color: #073D26; font-size: 15.5px;">Cashfree Online Gateway</strong>
            </div>
            <span style="font-size: 11.5px; background: #E6F4EA; color: #137333; font-weight: 700; padding: 3px 8px; border-radius: 4px;">
              Zero Extra Gateway Fee
            </span>
          </div>

          <p style="margin: 0 0 12px; font-size: 13.5px; color: #4B4336; line-height: 1.5;">
            Supports all Indian payment options: <strong>UPI</strong> (Google Pay, PhonePe, Paytm, BHIM), <strong>Credit &amp; Debit Cards</strong> (Visa, Mastercard, RuPay), and <strong>NetBanking</strong> across 50+ banks.
          </p>

          <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center; font-size: 12px; color: #6E6454;">
            <span style="background: #fff; border: 1px solid #DCD5C9; padding: 4px 8px; border-radius: 4px; font-weight: 600;">⚡ Instant UPI</span>
            <span style="background: #fff; border: 1px solid #DCD5C9; padding: 4px 8px; border-radius: 4px; font-weight: 600;">💳 Cards</span>
            <span style="background: #fff; border: 1px solid #DCD5C9; padding: 4px 8px; border-radius: 4px; font-weight: 600;">🏦 NetBanking</span>
          </div>
        </div>

        <!-- High-converting Cashfree CTA Button -->
        <button 
          type="submit" 
          id="checkout-submit-btn" 
          class="button button-primary"
          style="width: 100%; min-height: 56px; border-radius: 10px; font-size: 16.5px; font-weight: 700; letter-spacing: 0.3px; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 16px rgba(7, 61, 38, 0.25); cursor: pointer;"
        >
          <span>Pay <?= Helpers::formatPaise($total) ?> with Cashfree</span>
          <span aria-hidden="true">→</span>
        </button>

        <p class="checkout-guarantee" style="display: flex; align-items: center; justify-content: center; gap: 8px; margin: 14px 0 0; color: #6E6454; font-size: 12px;">
          <?= Icon::render('shield', ['size' => 16]) ?> 256-bit encrypted gateway. Your banking credentials are never stored.
        </p>
      </div>
    </form>

    <!-- Right Column: Modern Order Summary -->
    <aside class="checkout-summary-modern" aria-label="Items in this order">
      <div class="checkout-summary-heading">
        <span>Order Summary</span>
        <span style="font-size: 12.5px; font-family: var(--sans); font-weight: 600; color: #7B7265;">
          <?= count($cart['items']) ?> <?= count($cart['items']) === 1 ? 'item' : 'items' ?>
        </span>
      </div>

      <ul class="checkout-item-list" style="margin: 0; padding: 0; list-style: none;">
        <?php foreach ($cart['items'] as $item): 
          $img = !empty($item['image_url']) ? Helpers::asset($item['image_url']) : Helpers::asset('mixed-nuts-reference.jpg');
        ?>
          <li class="checkout-item-row" style="display: flex; align-items: center; gap: 14px; padding: 12px 0; border-bottom: 1px dashed #EDE6DC;">
            <div class="checkout-item-thumb" style="width: 52px; height: 52px; border-radius: 8px; overflow: hidden; position: relative; border: 1px solid #EAE3D5; flex-shrink: 0;">
              <img src="<?= $img ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;" />
              <span class="checkout-item-badge" style="position: absolute; top: -4px; right: -4px; background: #073D26; color: #fff; font-size: 11px; font-weight: 700; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <?= $item['quantity'] ?>
              </span>
            </div>
            <div class="checkout-item-meta" style="flex: 1; min-width: 0;">
              <strong style="display: block; font-size: 13.5px; color: #2A362D; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <?= htmlspecialchars($item['product_name']) ?>
              </strong>
              <span style="font-size: 12px; color: #7B7265;">
                <?= htmlspecialchars($item['variant_label']) ?>
              </span>
            </div>
            <div class="checkout-item-price" style="font-weight: 700; font-size: 14px; color: #073D26;">
              <?= Helpers::formatPaise((int)$item['line_total_paise']) ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>

      <dl class="checkout-totals" style="margin: 18px 0 0; padding: 14px 0 0; border-top: 1.5px solid #EDE6DC;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px;">
          <dt style="color: #6E6454;">Subtotal</dt>
          <dd style="font-weight: 600; color: #2A362D; margin: 0;"><?= Helpers::formatPaise($subtotal) ?></dd>
        </div>

        <?php if ($discount > 0): ?>
          <div class="discount-row" style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; color: #2E7D32;">
            <dt>Discount (<?= htmlspecialchars($coupon) ?>)</dt>
            <dd style="font-weight: 700; margin: 0;">−<?= Helpers::formatPaise($discount) ?></dd>
          </div>
        <?php endif; ?>

        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px;">
          <dt style="color: #6E6454;">Shipping</dt>
          <dd id="summary-shipping-val" style="font-weight: 700; color: <?= $shipping === 0 ? '#2E7D32' : '#073D26' ?>; margin: 0;">
            <?= $shipping === 0 ? 'Free' : Helpers::formatPaise($shipping) ?>
          </dd>
        </div>

        <div class="checkout-grand-total" style="display: flex; justify-content: space-between; align-items: baseline; padding-top: 14px; border-top: 2px solid #073D26;">
          <dt style="font-family: var(--serif); font-size: 19px; color: #073D26; font-weight: 700;">Total Payable</dt>
          <dd id="summary-total-val" style="font-family: var(--serif); font-size: 24px; color: #073D26; font-weight: 700; margin: 0;">
            <?= Helpers::formatPaise($total) ?>
          </dd>
        </div>
      </dl>

      <div style="text-align: center; margin-top: 18px;">
        <a href="<?= Helpers::url('cart') ?>" style="font-size: 13px; color: #073D26; font-weight: 600; text-decoration: underline;">
          ← Edit bag or offers
        </a>
      </div>

      <!-- Trust Assurance Box -->
      <div class="checkout-summary-trust-badge">
        <div style="font-size: 13px; font-weight: 700; color: #073D26; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
          <span>🌿</span> SVG Dry Fruits Promise
        </div>
        <ul style="margin: 0; padding-left: 18px; font-size: 12px; color: #5D4F3C; line-height: 1.6;">
          <li>100% natural, handpicked harvest</li>
          <li>Airtight seal packaging for crunch &amp; freshness</li>
          <li>Fast dispatch with live tracking updates</li>
        </ul>
      </div>
    </aside>
  </div>
</div>

<?php $googleMapsKey = config('google_maps_api_key', env('GOOGLE_MAPS_API_KEY', '')); ?>
<?php if (!empty($googleMapsKey)): ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($googleMapsKey) ?>&libraries=places&callback=initGooglePlacesAutocomplete" async defer></script>
<?php endif; ?>

<script>
var baseSubtotalPaise = <?= (int)$subtotal ?>;
var discountPaise = <?= (int)$discount ?>;
var currentShippingPaise = <?= (int)$shipping ?>;
var shippingRules = <?= json_encode(Models::getShippingOptions(true)) ?>;
var cartTotalWeightGrams = <?= (int)($cart['total_weight_grams'] ?? 500) ?>;
var cartTotalWeightKg = <?= (float)($cart['total_weight_kg'] ?? 0.5) ?>;
var currentShippingRule = null;

function formatINR(paise) {
  var rupees = paise / 100;
  return '₹' + rupees.toLocaleString('en-IN', { maximumFractionDigits: (paise % 100 === 0) ? 0 : 2 });
}

function resolveShippingRule(stateName) {
  var clean = (stateName || '').toLowerCase().trim();
  var matched = null;
  var fallbackAll = null;

  for (var i = 0; i < shippingRules.length; i++) {
    var opt = shippingRules[i];
    var targets = (opt.target_states || 'ALL').toLowerCase().trim();
    if (targets === 'all' || targets === '*' || targets === '') {
      if (!fallbackAll) fallbackAll = opt;
      continue;
    }
    if (clean !== '') {
      var parts = targets.split(',').map(function(s) { return s.trim(); });
      for (var j = 0; j < parts.length; j++) {
        var s = parts[j];
        if (s === clean || clean.indexOf(s) !== -1 || s.indexOf(clean) !== -1) {
          matched = opt;
          break;
        }
      }
      if (matched) break;
    }
  }

  return matched || fallbackAll || (shippingRules.length > 0 ? shippingRules[0] : null);
}

function handleStateChange(stateName) {
  var rule = resolveShippingRule(stateName);
  currentShippingRule = rule;

  var clean = (stateName || '').toLowerCase().trim();
  var isHome = (clean === 'telangana' || clean === 'andhra pradesh');

  var baseRatePaise = isHome ? 9000 : 10000;
  var rate12Paise = isHome ? 13000 : 15000;
  var rate2kgAbovePaise = isHome ? 15000 : 21000;
  var sla = isHome ? '1 – 3 Business Days' : '3 – 5 Business Days';
  var desc = 'Dispatched from our Hyderabad & Kakinada packing facilities. Shipping is calculated automatically based on destination state & order weight.';
  var ruleName = 'Direct Delivery to ' + (stateName || 'Your State');
  var freeAbove = null;

  if (rule) {
    sla = rule.estimated_days || sla;
    desc = rule.description || desc;
    ruleName = rule.name || ruleName;
    freeAbove = rule.free_above_paise ? parseInt(rule.free_above_paise, 10) : null;
    baseRatePaise = parseInt(rule.rate_paise, 10) || baseRatePaise;
    rate12Paise = rule.rate_1_2kg_paise ? parseInt(rule.rate_1_2kg_paise, 10) : (isHome ? 13000 : 15000);
    rate2kgAbovePaise = rule.rate_2kg_above_paise ? parseInt(rule.rate_2kg_above_paise, 10) : (isHome ? 15000 : 21000);
  }

  // Weight-based Tier Calculation
  var selectedRatePaise = baseRatePaise;
  var tierName = 'Below 1 kg';
  if (cartTotalWeightGrams >= 2000) {
    selectedRatePaise = rate2kgAbovePaise;
    tierName = '2 kg & Above';
  } else if (cartTotalWeightGrams >= 1000) {
    selectedRatePaise = rate12Paise;
    tierName = '1 – 2 kg';
  }

  if (freeAbove !== null && baseSubtotalPaise >= freeAbove) {
    selectedRatePaise = 0;
  }

  currentShippingPaise = selectedRatePaise;

  var stateEl = document.getElementById('shipping-rule-state');
  if (stateEl) stateEl.textContent = stateName;

  var nameEl = document.getElementById('shipping-rule-name');
  if (nameEl) nameEl.textContent = ruleName;

  var descEl = document.getElementById('shipping-rule-desc');
  if (descEl) descEl.textContent = desc;

  var slaEl = document.getElementById('shipping-rule-sla');
  if (slaEl) slaEl.textContent = '⏱ Estimated: ' + sla;

  var tierBadge = document.getElementById('shipping-tier-badge');
  if (tierBadge) tierBadge.textContent = 'Tier: ' + tierName;

  var badgeEl = document.getElementById('shipping-rate-badge');
  if (badgeEl) {
    if (selectedRatePaise === 0) {
      badgeEl.textContent = 'FREE DELIVERY';
      badgeEl.style.color = '#2E7D32';
      badgeEl.style.background = '#EAF5EC';
    } else {
      badgeEl.textContent = formatINR(selectedRatePaise);
      badgeEl.style.color = '#073D26';
      badgeEl.style.background = '#F4EFE6';
    }
  }

  var hintEl = document.getElementById('shipping-free-hint');
  if (hintEl) {
    if (freeAbove !== null && baseSubtotalPaise < freeAbove) {
      var diff = freeAbove - baseSubtotalPaise;
      hintEl.textContent = '• Add ' + formatINR(diff) + ' more for FREE delivery!';
      hintEl.style.display = 'inline';
    } else if (freeAbove !== null && baseSubtotalPaise >= freeAbove) {
      hintEl.textContent = '• 🎉 You unlocked FREE delivery!';
      hintEl.style.display = 'inline';
    } else {
      hintEl.style.display = 'none';
    }
  }

  var summaryShipping = document.getElementById('summary-shipping-val');
  if (summaryShipping) {
    summaryShipping.textContent = (selectedRatePaise === 0) ? 'Free' : formatINR(selectedRatePaise);
    summaryShipping.style.color = (selectedRatePaise === 0) ? '#2E7D32' : '#073D26';
  }

  var newTotalPaise = Math.max(0, baseSubtotalPaise - discountPaise + selectedRatePaise);
  var summaryTotal = document.getElementById('summary-total-val');
  if (summaryTotal) {
    summaryTotal.textContent = formatINR(newTotalPaise);
  }

  var submitBtn = document.getElementById('checkout-submit-btn');
  if (submitBtn) {
    submitBtn.innerHTML = '<span>Pay ' + formatINR(newTotalPaise) + ' with Cashfree</span> <span aria-hidden="true">→</span>';
  }
}

// =========================================================================
// BULLETPROOF MULTI-TIER LOCATION AUTO-FETCH ENGINE
// Tier 1: GPS via navigator.geolocation (5s timeout)
// Tier 2: BigDataCloud client reverse geocode
// Tier 3: Internal /api/locate.php + ipapi.co fallback (Works on HTTP & zero permissions)
// Tier 4: India Post PIN code live resolver
// =========================================================================

function autoFetchCurrentLocation() {
  var btn = document.getElementById('btn-use-location');
  var btnText = document.getElementById('btn-use-location-text');

  if (btn) btn.disabled = true;
  if (btnText) btnText.textContent = 'Detecting…';
  showLocationStatus('Detecting your location…', '#EAF5EC', '#073D26');

  // If navigator.geolocation exists and we are in a supported environment, try GPS with strict 5-second timeout
  var gpsTried = false;
  if (navigator.geolocation && (window.isSecureContext !== false || location.hostname === 'localhost' || location.hostname === '127.0.0.1')) {
    gpsTried = true;
    navigator.geolocation.getCurrentPosition(
      function(pos) {
        var lat = pos.coords.latitude;
        var lon = pos.coords.longitude;
        if (btnText) btnText.textContent = 'Resolving Address…';

        // Reverse geocode via BigDataCloud (CORS enabled for browsers)
        fetch('https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=' + lat + '&longitude=' + lon + '&localityLanguage=en')
          .then(function(r) { return r.json(); })
          .then(function(data) {
            applyLocationData({
              road: data.locality || data.city || '',
              city: data.city || data.locality || '',
              state: data.principalSubdivision || '',
              postcode: data.postcode || ''
            }, 'GPS');
          })
          .catch(function() {
            // Fallback to internal IP locator if reverse geocode fails
            fallbackToIpLocation('GPS address lookup timed out. Detected via Network IP:');
          });
      },
      function(err) {
        // Geolocation denied, unavailable, or timed out -> Fall back to IP instantly!
        fallbackToIpLocation('Detected via Network IP:');
      },
      { timeout: 5000, enableHighAccuracy: true, maximumAge: 60000 }
    );
  } else {
    // Non-HTTPS or geolocation not supported -> directly use IP fallback
    fallbackToIpLocation('Detected via Network IP:');
  }
}

function fallbackToIpLocation(successPrefix) {
  var btn = document.getElementById('btn-use-location');
  var btnText = document.getElementById('btn-use-location-text');

  // Try internal API first (bypasses any adblockers or browser mixed-content restrictions)
  var apiUrl = (window.SVG_APP && window.SVG_APP.apiUrl) ? window.SVG_APP.apiUrl : '/api';
  fetch(apiUrl + '/locate.php')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data && (data.city || data.region)) {
        applyLocationData({
          road: '',
          city: data.city || '',
          state: data.region || '',
          postcode: data.postal || ''
        }, successPrefix || 'Network IP');
      } else {
        throw new Error('No region');
      }
    })
    .catch(function() {
      // Secondary fallback to public ipapi
      fetch('https://ipapi.co/json/')
        .then(function(r) { return r.json(); })
        .then(function(data) {
          applyLocationData({
            road: '',
            city: data.city || '',
            state: data.region || '',
            postcode: data.postal || ''
          }, successPrefix || 'Network IP');
        })
        .catch(function() {
          showLocationStatus('Please enter your city, PIN code and select your state manually.', '#FFF8E6', '#A35A00');
          if (btn) btn.disabled = false;
          if (btnText) btnText.textContent = 'Auto-Detect Location';
        });
    });
}

function applyLocationData(addr, source) {
  var btn = document.getElementById('btn-use-location');
  var btnText = document.getElementById('btn-use-location-text');

  var city = addr.city || '';
  var state = addr.state || '';
  var postcode = (addr.postcode || '').replace(/\D/g, '').slice(0, 6);
  var road = addr.road || '';

  if (road) {
    var streetEl = document.getElementById('street');
    if (streetEl && !streetEl.value.trim()) {
      streetEl.value = road;
    }
  }

  if (city) {
    var cityEl = document.getElementById('city');
    if (cityEl) cityEl.value = city;
  }

  if (postcode && postcode.length === 6) {
    var pinEl = document.getElementById('pincode');
    if (pinEl) {
      pinEl.value = postcode;
      handlePincodeInput(postcode);
    }
  }

  if (state) {
    matchAndSelectState(state);
  }

  var displayLabel = (city ? city + ', ' : '') + (state || 'Your Location');
  showLocationStatus('✓ ' + (source || 'Auto') + ' Location Applied: ' + displayLabel, '#EAF5EC', '#073D26');

  if (btn) btn.disabled = false;
  if (btnText) btnText.textContent = '✓ Location Applied';
}

function showLocationStatus(text, bg, color) {
  var statusEl = document.getElementById('location-fetch-status');
  if (statusEl) {
    statusEl.innerHTML = text;
    statusEl.style.background = bg;
    statusEl.style.color = color;
    statusEl.style.display = 'flex';
  }
}

function matchAndSelectState(stateStr) {
  var stateSelect = document.getElementById('state');
  if (!stateSelect) return;
  var target = stateStr.toLowerCase().trim();

  // Normalize common aliases
  if (target === 'ts' || target.indexOf('telangana') !== -1) target = 'telangana';
  if (target === 'ap' || target.indexOf('andhra') !== -1) target = 'andhra pradesh';
  if (target.indexOf('delhi') !== -1) target = 'delhi';

  for (var i = 0; i < stateSelect.options.length; i++) {
    var optVal = stateSelect.options[i].value.toLowerCase().trim();
    if (optVal === target || target.indexOf(optVal) !== -1 || optVal.indexOf(target) !== -1) {
      stateSelect.selectedIndex = i;
      handleStateChange(stateSelect.options[i].value);
      return;
    }
  }
}

// Live 6-digit PIN code auto-fill via India Post API
var pinTimer = null;
function handlePincodeInput(val) {
  var pin = (val || '').replace(/\D/g, '').slice(0, 6);
  var pinStatus = document.getElementById('pin-status-pill');

  if (pin.length !== 6) {
    if (pinStatus) pinStatus.style.display = 'none';
    return;
  }

  if (pinStatus) {
    pinStatus.textContent = 'Verifying PIN code…';
    pinStatus.style.color = '#7B7265';
    pinStatus.style.display = 'block';
  }

  clearTimeout(pinTimer);
  pinTimer = setTimeout(function() {
    fetch('https://api.postalpincode.in/pincode/' + pin)
      .then(function(r) { return r.json(); })
      .then(function(res) {
        if (Array.isArray(res) && res[0] && res[0].Status === 'Success' && Array.isArray(res[0].PostOffice) && res[0].PostOffice.length > 0) {
          var po = res[0].PostOffice[0];
          var district = po.District || po.Circle || '';
          var state = po.State || '';
          var officeName = po.Name || '';

          var cityInput = document.getElementById('city');
          if (cityInput && (!cityInput.value.trim() || cityInput.value === district || cityInput.value === officeName)) {
            cityInput.value = district || officeName;
          }

          if (state) {
            matchAndSelectState(state);
          }

          if (pinStatus) {
            pinStatus.innerHTML = '✓ ' + (officeName ? officeName + ', ' : '') + district + ' (' + state + ')';
            pinStatus.style.color = '#2E7D32';
            pinStatus.style.display = 'block';
          }
        } else {
          if (pinStatus) {
            pinStatus.textContent = 'PIN code entered';
            pinStatus.style.color = '#7B7265';
          }
        }
      })
      .catch(function() {
        if (pinStatus) pinStatus.style.display = 'none';
      });
  }, 250);
}

function initGooglePlacesAutocomplete() {
  var input = document.getElementById('street');
  if (!input || !window.google || !google.maps || !google.maps.places) return;
  var autocomplete = new google.maps.places.Autocomplete(input, {
    types: ['geocode', 'establishment'],
    componentRestrictions: { country: 'in' }
  });
  autocomplete.addListener('place_changed', function() {
    var place = autocomplete.getPlace();
    if (!place || !place.address_components) return;
    var streetParts = [];
    var city = '';
    var state = '';
    var postalCode = '';
    for (var i = 0; i < place.address_components.length; i++) {
      var c = place.address_components[i];
      var types = c.types;
      if (types.indexOf('street_number') !== -1 || types.indexOf('premise') !== -1) {
        streetParts.push(c.long_name);
      }
      if (types.indexOf('route') !== -1 || types.indexOf('sublocality') !== -1 || types.indexOf('sublocality_level_1') !== -1) {
        streetParts.push(c.long_name);
      }
      if (types.indexOf('locality') !== -1) {
        city = c.long_name;
      }
      if (types.indexOf('administrative_area_level_1') !== -1) {
        state = c.long_name;
      }
      if (types.indexOf('postal_code') !== -1) {
        postalCode = c.long_name.replace(/\D/g, '').slice(0, 6);
      }
    }
    if (streetParts.length > 0) input.value = streetParts.join(', ');
    if (city && document.getElementById('city')) document.getElementById('city').value = city;
    if (postalCode && document.getElementById('pincode')) {
      document.getElementById('pincode').value = postalCode;
      handlePincodeInput(postalCode);
    }
    if (state) matchAndSelectState(state);
  });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
  var stateSelect = document.getElementById('state');
  if (stateSelect) {
    handleStateChange(stateSelect.value);
  }
  var pinInput = document.getElementById('pincode');
  if (pinInput && pinInput.value.length === 6) {
    handlePincodeInput(pinInput.value);
  }
});

function handleCheckoutSubmit(e) {
  e.preventDefault();
  var btn = document.getElementById('checkout-submit-btn');
  var errBanner = document.getElementById('checkout-err-banner');
  var errMsg = document.getElementById('checkout-err-msg');

  if (errBanner) errBanner.style.display = 'none';
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<span>Connecting to Cashfree…</span>';
  }

  var stateValue = document.getElementById('state').value.trim();
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
    state: stateValue,
    shipping_state: stateValue,
    pincode: document.getElementById('pincode').value.trim(),
    shipping_pincode: document.getElementById('pincode').value.trim(),
    shipping_option_id: currentShippingRule ? currentShippingRule.id : ''
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
              handleStateChange(document.getElementById('state').value);
            }
            return;
          }
          if (result && result.redirect) {
            return;
          }
          if (result && result.paymentDetails) {
            window.location.href = res.redirect_url;
          }
        }).catch(function(cfErr) {
          console.error('Cashfree SDK checkout exception:', cfErr);
          if (typeof cashfree.checkout === 'function') {
            cashfree.checkout({
              paymentSessionId: res.payment_session_id,
              redirectTarget: '_self'
            });
          } else {
            window.location.href = res.redirect_url;
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
        handleStateChange(document.getElementById('state').value);
      }
    });
}
</script>

<?php require __DIR__ . '/layouts/footer.php'; ?>

