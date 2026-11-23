<?php
/**
 * SVG Dry Fruits - Guest Checkout Page
 * Clean, fast, distraction-free checkout with background state & weight shipping calculation
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
      
      <!-- 1. Contact Details -->
      <div class="checkout-section-box" style="border-radius: 12px; box-shadow: 0 2px 12px rgba(7, 61, 38, 0.04);">
        <h2>1. Contact details</h2>
        <div class="form-group-row">
          <div class="form-group">
            <label for="name">Full name <span class="req">*</span></label>
            <input id="name" name="name" required placeholder="e.g. Ramesh Kumar" autocomplete="name" style="border-radius: 6px;" />
          </div>
          <div class="form-group">
            <label for="phone">Mobile number <span class="req">*</span></label>
            <div class="phone-input-wrap" style="border-radius: 6px; overflow: hidden;">
              <span class="phone-prefix" style="font-weight: 600; color: #073D26; background: #FAF7F2;">+91</span>
              <input id="phone" name="phone" type="tel" required placeholder="9876543210" maxlength="10" pattern="[6-9][0-9]{9}" autocomplete="tel" />
            </div>
          </div>
        </div>
        <div class="form-group">
          <label for="email">Email address <span class="opt">(optional)</span></label>
          <input id="email" name="email" type="email" placeholder="ramesh@example.com" autocomplete="email" style="border-radius: 6px;" />
          <span class="field-hint">We'll send order tracking updates and Cashfree receipt if provided.</span>
        </div>
      </div>

      <!-- 2. Delivery Address -->
      <div class="checkout-section-box" style="border-radius: 12px; box-shadow: 0 2px 12px rgba(7, 61, 38, 0.04);">
        <h2>2. Delivery address</h2>

        <div class="form-group">
          <label for="street">Street address / Flat / House no <span class="req">*</span></label>
          <input id="street" name="street" required placeholder="Flat 402, Lotus Residency, MG Road" autocomplete="street-address" style="border-radius: 6px;" />
        </div>

        <div class="form-group">
          <label for="landmark">Landmark <span class="opt">(optional)</span></label>
          <input id="landmark" name="landmark" placeholder="Near City Hospital" style="border-radius: 6px;" />
        </div>

        <div class="form-group-row three-col">
          <div class="form-group">
            <label for="pincode">PIN code <span class="req">*</span></label>
            <input id="pincode" name="pincode" required maxlength="6" pattern="[0-9]{6}" placeholder="500001" autocomplete="postal-code" oninput="handlePincodeInput(this.value)" style="border-radius: 6px;" />
            <div id="pin-status-pill" style="font-size: 11.5px; color: #2E7D32; font-weight: 600; margin-top: 4px; display: none;"></div>
          </div>
          <div class="form-group">
            <label for="city">City / Town <span class="req">*</span></label>
            <input id="city" name="city" required placeholder="Hyderabad" autocomplete="address-level2" style="border-radius: 6px;" />
          </div>
          <div class="form-group">
            <label for="state">State <span class="req">*</span></label>
            <select id="state" name="state" required onchange="handleStateChange(this.value)" style="height: 48px; padding: 0 12px; border: 1px solid #d3cfc3; border-radius: 6px; font-size: 14px; background: #fff; width: 100%; cursor: pointer;">
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

      <!-- 3. Payment Method -->
      <div class="checkout-section-box payment-section-box" style="border-radius: 12px; box-shadow: 0 2px 12px rgba(7, 61, 38, 0.04);">
        <h2>3. Payment method</h2>
        <div class="payment-method-card" style="border-radius: 8px;">
          <div class="payment-method-header">
            <?= Icon::render('card', ['size' => 24]) ?>
            <strong>Cashfree Secure Online Payment</strong>
          </div>
          <p>Pay securely using UPI (Google Pay, PhonePe, Paytm), NetBanking, Credit or Debit Cards. Cash on delivery is not offered.</p>
        </div>

        <button type="submit" id="checkout-submit-btn" class="button button-primary checkout-submit-btn" style="border-radius: 8px; font-weight: 700; font-size: 16px;">
          Pay <?= Helpers::formatPaise($total) ?> with Cashfree
        </button>

        <p class="checkout-guarantee">
          <?= Icon::render('shield', ['size' => 16]) ?> 256-bit encrypted gateway. Your card details are never stored on our server.
        </p>
      </div>
    </form>

    <!-- Right column: Order Summary -->
    <aside class="checkout-summary" aria-label="Items in this order" style="border-radius: 12px; box-shadow: 0 2px 12px rgba(7, 61, 38, 0.04);">
      <h2>Order summary</h2>
      <ul class="checkout-item-list">
        <?php foreach ($cart['items'] as $item): 
          $img = !empty($item['image_url']) ? Helpers::asset($item['image_url']) : Helpers::asset('mixed-nuts-reference.jpg');
        ?>
          <li class="checkout-item-row">
            <div class="checkout-item-thumb" style="border-radius: 8px; overflow: hidden;">
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
          <dd id="summary-shipping-val" style="color: #1B3B2B; font-weight: 700;">
            <span id="shipping-amount-text" style="color: #666; font-size: 13px; font-weight: 500;">Enter PIN to calculate</span>
          </dd>
        </div>
        <div id="shipping-details-note" style="display: none; font-size: 11.5px; color: #4A5568; margin-top: -6px; margin-bottom: 8px;">
          🚚 <span id="shipping-zone-text">Shadowfax Express</span> &bull; <span id="shipping-eta-text">1-2 Business Days</span>
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

<?php $googleMapsKey = env('GOOGLE_MAPS_API_KEY', ''); ?>
<?php if (!empty($googleMapsKey)): ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($googleMapsKey) ?>&libraries=places&callback=initGooglePlacesAutocomplete" async defer></script>
<?php endif; ?>

<script>
var baseSubtotalPaise = <?= (int)$subtotal ?>;
var discountPaise = <?= (int)$discount ?>;
var currentShippingPaise = 0;
var cartTotalWeightGrams = <?= (int)($cart['total_weight_grams'] ?? 500) ?>;
var cartTotalWeightKg = <?= (float)($cart['total_weight_kg'] ?? 0.5) ?>;
if (cartTotalWeightKg <= 0) cartTotalWeightKg = 0.5;
var currentShippingRate = null;
var currentShippingRule = { id: 'shadowfax_express', name: 'Shadowfax Express' };

function formatINR(paise) {
  var rupees = paise / 100;
  return '₹' + rupees.toLocaleString('en-IN', { minimumFractionDigits: (paise % 100 === 0) ? 0 : 2, maximumFractionDigits: 2 });
}

// Calculate Shadowfax rate card instantly in JS (matching Shadowfax::calculateShippingRate)
function calculateShadowfaxRateJS(destPin, weightKg) {
  destPin = (destPin || '').replace(/\D/g, '');
  if (destPin.length !== 6) return null;
  weightKg = Math.max(0.2, weightKg || 0.5);

  var slabs = Math.ceil(weightKg / 0.5);
  var billedWeightKg = slabs * 0.5;
  var destPrefix2 = parseInt(destPin.substring(0, 2), 10);

  var zone = 'Rest of India (National)';
  var baseRate = 80.00;
  var addlRatePer500g = 55.00;
  var etaDays = '3 - 5 Business Days';

  if ([51, 52, 53].indexOf(destPrefix2) !== -1) {
    zone = 'Intra-State (Andhra Pradesh)';
    baseRate = 45.00;
    addlRatePer500g = 30.00;
    etaDays = '1 - 2 Business Days';
  } else if (destPrefix2 === 50 || (destPrefix2 >= 56 && destPrefix2 <= 69)) {
    zone = 'Regional South (Telangana, Karnataka, TN, Kerala)';
    baseRate = 60.00;
    addlRatePer500g = 40.00;
    etaDays = '2 - 3 Business Days';
  } else if ([11, 40, 70].indexOf(destPrefix2) !== -1) {
    zone = 'Metro Hub (Delhi NCR, Mumbai, Kolkata)';
    baseRate = 70.00;
    addlRatePer500g = 45.00;
    etaDays = '2 - 4 Business Days';
  }

  var baseFreight = baseRate + (Math.max(0, slabs - 1) * addlRatePer500g);
  var fuelSurcharge = Math.round(baseFreight * 0.10 * 100) / 100;
  var netFreight = baseFreight + fuelSurcharge;
  var gst = Math.round(netFreight * 0.18 * 100) / 100;
  var totalCost = Math.round((netFreight + gst) * 100) / 100;
  var totalPaise = Math.round(totalCost * 100);

  return {
    destPin: destPin,
    zone: zone,
    totalCost: totalCost,
    totalPaise: totalPaise,
    etaDays: etaDays,
    breakdown: {
      base: baseFreight,
      fuel: fuelSurcharge,
      gst: gst
    }
  };
}

function applyShippingRate(rate) {
  currentShippingPaise = rate.totalPaise;

  var summaryShipping = document.getElementById('summary-shipping-val');
  if (summaryShipping) {
    summaryShipping.textContent = '₹' + rate.totalCost.toFixed(2);
    summaryShipping.style.color = '#1B3B2B';
  }

  var shippingDetails = document.getElementById('shipping-details-note');
  var zoneText = document.getElementById('shipping-zone-text');
  var etaText = document.getElementById('shipping-eta-text');
  if (shippingDetails && zoneText && etaText) {
    zoneText.textContent = rate.zone;
    etaText.textContent = rate.etaDays;
    shippingDetails.style.display = 'block';
  }

  var newTotalPaise = Math.max(0, baseSubtotalPaise - discountPaise + currentShippingPaise);
  var summaryTotal = document.getElementById('summary-total-val');
  if (summaryTotal) {
    summaryTotal.textContent = formatINR(newTotalPaise);
  }

  var submitBtn = document.getElementById('checkout-submit-btn');
  if (submitBtn) {
    submitBtn.textContent = 'Pay ' + formatINR(newTotalPaise) + ' with Cashfree';
  }
}

function handleStateChange(stateName) {
  var pinInput = document.getElementById('pincode');
  if (pinInput && pinInput.value.replace(/\D/g, '').length === 6) {
    handlePincodeInput(pinInput.value);
  }
}

// Live 6-digit PIN code auto-fill via India Post API + Shadowfax rate calculation
var pinTimer = null;
function handlePincodeInput(val) {
  var pin = (val || '').replace(/\D/g, '').slice(0, 6);
  var pinStatus = document.getElementById('pin-status-pill');
  var summaryShipping = document.getElementById('summary-shipping-val');
  var shippingDetails = document.getElementById('shipping-details-note');
  var summaryTotal = document.getElementById('summary-total-val');
  var submitBtn = document.getElementById('checkout-submit-btn');

  if (pin.length !== 6) {
    if (pinStatus) pinStatus.style.display = 'none';
    if (shippingDetails) shippingDetails.style.display = 'none';
    currentShippingPaise = 0;
    if (summaryShipping) {
      summaryShipping.innerHTML = '<span style="color: #666; font-size: 13px; font-weight: 500;">Enter PIN to calculate</span>';
    }
    var baseTotal = Math.max(0, baseSubtotalPaise - discountPaise);
    if (summaryTotal) summaryTotal.textContent = formatINR(baseTotal);
    if (submitBtn) submitBtn.textContent = 'Pay ' + formatINR(baseTotal) + ' with Cashfree';
    return;
  }

  // Instant calculation with client-side Shadowfax rate card
  var instantRate = calculateShadowfaxRateJS(pin, cartTotalWeightKg);
  if (instantRate) {
    applyShippingRate(instantRate);
  }

  clearTimeout(pinTimer);
  pinTimer = setTimeout(function() {
    // 1. Fetch live server calculation & serviceability check from Shadowfax API
    fetch(window.SVG_APP.apiUrl + '/shipping-calc.php?pincode=' + pin)
      .then(function(r) { return r.json(); })
      .then(function(res) {
        if (res.success) {
          applyShippingRate({
            destPin: res.pincode,
            zone: res.zone,
            totalCost: res.shipping_cost,
            totalPaise: res.shipping_paise,
            etaDays: res.estimated_timeline
          });
          if (pinStatus) {
            pinStatus.innerHTML = res.serviceable 
              ? '✓ ' + res.service_message 
              : '⚠️ ' + res.service_message;
            pinStatus.style.color = res.serviceable ? '#2E7D32' : '#C53030';
            pinStatus.style.display = 'block';
          }
        }
      })
      .catch(function(err) {
        console.warn('Shipping API verification:', err);
      });

    // 2. Auto-fill City & State via India Post
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
        }
      })
      .catch(function() {});
  }, 250);
}

function matchAndSelectState(stateStr) {
  var stateSelect = document.getElementById('state');
  if (!stateSelect) return;
  var target = stateStr.toLowerCase().trim();

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

  var pinValue = document.getElementById('pincode').value.replace(/\D/g, '');
  if (pinValue.length !== 6) {
    if (errMsg) errMsg.textContent = 'Please enter a valid 6-digit delivery PIN code to calculate shipping charges.';
    if (errBanner) errBanner.style.display = 'flex';
    if (btn) {
      btn.disabled = false;
      var baseTotal = Math.max(0, baseSubtotalPaise - discountPaise);
      btn.textContent = 'Pay ' + formatINR(baseTotal) + ' with Cashfree';
    }
    document.getElementById('pincode').focus();
    return;
  }

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
    shipping_option_id: (currentShippingRule && currentShippingRule.id) ? currentShippingRule.id : 'shadowfax_express'
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
              handlePincodeInput(document.getElementById('pincode').value);
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
        handlePincodeInput(document.getElementById('pincode').value);
      }
    });
}
</script>

<?php require __DIR__ . '/layouts/footer.php'; ?>


