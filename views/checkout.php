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
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
          <h2 style="margin: 0;">2. Delivery address</h2>
          <button
            type="button"
            id="btn-use-location"
            onclick="autoFetchCurrentLocation()"
            style="display: inline-flex; align-items: center; gap: 6px; background: #EAF5EC; color: #073D26; border: 1.5px solid #073D26; padding: 7px 14px; border-radius: 6px; font-size: 13px; font-weight: 700; cursor: pointer; transition: all 0.2s;"
            title="Automatically detect address & state via GPS / Google Maps"
          >
            <span>📍</span> <span id="btn-use-location-text">Use Current Location</span>
          </button>
        </div>
        <div id="location-fetch-status" style="display: none; margin-bottom: 14px; font-size: 12.5px; padding: 8px 14px; border-radius: 6px;"></div>

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
            <select id="state" name="state" required onchange="handleStateChange(this.value)" style="height: 44px; padding: 0 12px; border: 1px solid var(--line); border-radius: 6px; font-size: 14px; background: #fff; width: 100%;">
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

      <!-- Step 3: Automated Weight & State-Based Delivery Rate -->
      <div class="checkout-section-box shipping-method-section-box" style="margin-bottom: 24px;">
        <h2>3. Delivery &amp; Shipping</h2>

        <div id="shipping-rate-card" style="padding: 16px 18px; border: 2px solid #073D26; border-radius: 10px; background: #F8FBF9; display: flex; align-items: flex-start; gap: 14px;">
          <div style="font-size: 26px; line-height: 1; padding-top: 2px;">🚚</div>
          <div style="flex: 1;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; flex-wrap: wrap; gap: 6px;">
              <strong id="shipping-rule-name" style="color: #073D26; font-size: 16px;">
                Direct Delivery to <span id="shipping-rule-state">Telangana</span>
              </strong>
              <span id="shipping-rate-badge" style="font-weight: 700; font-size: 15px; color: #073D26; background: #EAF5EC; padding: 4px 10px; border-radius: 6px;">
                <?= $shipping === 0 ? 'FREE DELIVERY' : Helpers::formatPaise($shipping) ?>
              </span>
            </div>
            <div style="margin: 4px 0 8px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
              <span id="shipping-weight-badge" style="font-size: 12px; background: #FAF7F2; border: 1px solid #DFD7CA; color: #5D4F3C; padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                ⚖️ Bag Weight: <?= $cart['total_weight_kg'] ?? 0.5 ?> kg (<?= $cart['total_weight_grams'] ?? 500 ?>g)
              </span>
              <span id="shipping-tier-badge" style="font-size: 12px; background: #FAF7F2; border: 1px solid #DFD7CA; color: #073D26; padding: 2px 8px; border-radius: 4px; font-weight: 700;">
                Tier: Below 1 kg
              </span>
            </div>
            <p id="shipping-rule-desc" style="margin: 4px 0 6px; font-size: 13.5px; color: #444; line-height: 1.45;">
              Dispatched from our Hyderabad &amp; Kakinada packing facilities. Shipping is calculated automatically based on destination state &amp; bag weight.
            </p>
            <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
              <span id="shipping-rule-sla" style="display: inline-block; font-size: 11.5px; padding: 2px 8px; border-radius: 4px; background: #F4EFE6; color: #5D4F3C; font-weight: 600;">
                ⏱ Estimated: 1 – 3 Business Days
              </span>
              <span id="shipping-free-hint" style="font-size: 12px; color: #8E6D38; font-weight: 600;"></span>
            </div>
          </div>
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
  }

  var newTotalPaise = Math.max(0, baseSubtotalPaise - discountPaise + selectedRatePaise);
  var summaryTotal = document.getElementById('summary-total-val');
  if (summaryTotal) {
    summaryTotal.textContent = formatINR(newTotalPaise);
  }

  var submitBtn = document.getElementById('checkout-submit-btn');
  if (submitBtn) {
    submitBtn.textContent = 'Pay ' + formatINR(newTotalPaise) + ' with Cashfree';
  }
}

// 1-Click Current Location Auto-Fetch
function autoFetchCurrentLocation() {
  var btn = document.getElementById('btn-use-location');
  var btnText = document.getElementById('btn-use-location-text');

  if (!navigator.geolocation) {
    showLocationStatus('Geolocation is not supported by your browser. Please enter your address manually.', '#fff3cd', '#856404');
    return;
  }

  if (btn) btn.disabled = true;
  if (btnText) btnText.textContent = 'Detecting Location…';
  showLocationStatus('Accessing your GPS location… please allow location permission if prompted.', '#eaf4ee', '#073D26');

  navigator.geolocation.getCurrentPosition(
    function(position) {
      var lat = position.coords.latitude;
      var lon = position.coords.longitude;
      if (btnText) btnText.textContent = 'Fetching Address…';

      // Reverse geocode via OpenStreetMap Nominatim with BigDataCloud fallback
      fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + lat + '&lon=' + lon)
        .then(function(r) { return r.json(); })
        .then(function(data) {
          applyLocationData(data.address || {});
          showLocationStatus('✓ Address & State detected and filled successfully!', '#e6f4ea', '#137333');
          if (btn) btn.disabled = false;
          if (btnText) btnText.textContent = '✓ Location Applied';
        })
        .catch(function() {
          // Fallback to client-side reverse geocode endpoint
          fetch('https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=' + lat + '&longitude=' + lon + '&localityLanguage=en')
            .then(function(r) { return r.json(); })
            .then(function(bdc) {
              applyLocationData({
                road: bdc.locality || bdc.city || '',
                city: bdc.city || bdc.principalSubdivision || '',
                state: bdc.principalSubdivision || '',
                postcode: bdc.postcode || ''
              });
              showLocationStatus('✓ Location detected and filled successfully!', '#e6f4ea', '#137333');
              if (btn) btn.disabled = false;
              if (btnText) btnText.textContent = '✓ Location Applied';
            })
            .catch(function() {
              showLocationStatus('Could not resolve street address from GPS. Please enter your street and city manually.', '#fff3cd', '#856404');
              if (btn) btn.disabled = false;
              if (btnText) btnText.textContent = 'Use Current Location';
            });
        });
    },
    function(err) {
      var msg = 'Location request was declined or timed out. Please enter your address manually.';
      if (err.code === 1) {
        msg = 'Location permission was denied. Please allow location access in your browser or enter address manually.';
      }
      showLocationStatus(msg, '#f8d7da', '#721c24');
      if (btn) btn.disabled = false;
      if (btnText) btnText.textContent = 'Use Current Location';
    },
    { timeout: 12000, enableHighAccuracy: true }
  );
}

function showLocationStatus(text, bg, color) {
  var statusEl = document.getElementById('location-fetch-status');
  if (statusEl) {
    statusEl.textContent = text;
    statusEl.style.background = bg;
    statusEl.style.color = color;
    statusEl.style.display = 'block';
  }
}

function applyLocationData(addr) {
  var road = addr.road || addr.suburb || addr.neighbourhood || addr.residential || '';
  var house = addr.house_number || addr.building || '';
  var streetParts = [];
  if (house) streetParts.push(house);
  if (road) streetParts.push(road);
  if (addr.suburb && addr.suburb !== road) streetParts.push(addr.suburb);

  var streetInput = document.getElementById('street');
  if (streetInput && streetParts.length > 0 && !streetInput.value.trim()) {
    streetInput.value = streetParts.join(', ');
  }

  var city = addr.city || addr.town || addr.village || addr.county || addr.state_district || '';
  var cityInput = document.getElementById('city');
  if (cityInput && city) {
    cityInput.value = city;
  }

  var pincode = (addr.postcode || '').replace(/\D/g, '').slice(0, 6);
  var pinInput = document.getElementById('pincode');
  if (pinInput && pincode.length === 6) {
    pinInput.value = pincode;
  }

  var state = addr.state || '';
  if (state) {
    matchAndSelectState(state);
  }
}

function matchAndSelectState(stateStr) {
  var stateSelect = document.getElementById('state');
  if (!stateSelect) return;
  var target = stateStr.toLowerCase().trim();
  for (var i = 0; i < stateSelect.options.length; i++) {
    var optVal = stateSelect.options[i].value.toLowerCase().trim();
    if (optVal === target || target.indexOf(optVal) !== -1 || optVal.indexOf(target) !== -1) {
      stateSelect.selectedIndex = i;
      handleStateChange(stateSelect.options[i].value);
      return;
    }
  }
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
    if (postalCode && document.getElementById('pincode')) document.getElementById('pincode').value = postalCode;
    if (state) matchAndSelectState(state);
  });
}

// Initialize state-based rate on page load
document.addEventListener('DOMContentLoaded', function() {
  var stateSelect = document.getElementById('state');
  if (stateSelect) {
    handleStateChange(stateSelect.value);
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
    btn.textContent = 'Redirecting to Cashfree…';
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
            // Browser navigating to 3DS / bank
            return;
          }
          if (result && result.paymentDetails) {
            // Payment success! Redirect to order confirmed screen
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
