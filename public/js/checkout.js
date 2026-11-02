/**
 * SVG Dry Fruits - Checkout & Cashfree PG JS Integration
 */

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    const checkoutForm = document.getElementById('checkout-form');
    const placeOrderBtn = document.getElementById('btn-place-order');
    const couponForm = document.getElementById('coupon-form');
    const couponInput = document.getElementById('coupon-code-input');
    const couponMessage = document.getElementById('coupon-message');

    // --- Coupon Handling ---
    if (couponForm) {
      couponForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const code = couponInput.value.trim();
        if (!code) return;

        couponMessage.textContent = 'Applying coupon...';
        couponMessage.style.color = 'var(--muted)';

        fetch(window.SVG_APP.apiUrl + '/coupon.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'apply', code: code })
        })
          .then(r => r.json())
          .then(res => {
            if (res.success) {
              couponMessage.textContent = res.message;
              couponMessage.style.color = '#137333';
              window.showToast("Coupon applied!");
              setTimeout(() => window.location.reload(), 600);
            } else {
              couponMessage.textContent = res.error || 'Failed to apply coupon';
              couponMessage.style.color = '#c5221f';
            }
          })
          .catch(() => {
            couponMessage.textContent = 'Network error applying coupon.';
            couponMessage.style.color = '#c5221f';
          });
      });
    }

    window.removeCoupon = function () {
      fetch(window.SVG_APP.apiUrl + '/coupon.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'remove' })
      })
        .then(r => r.json())
        .then(() => {
          window.location.reload();
        });
    };

    // --- Checkout Submission & Cashfree Launch ---
    if (checkoutForm && placeOrderBtn) {
      checkoutForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const name = document.getElementById('customer-name')?.value.trim();
        const phone = document.getElementById('customer-phone')?.value.trim();
        const email = document.getElementById('customer-email')?.value.trim();
        const street = document.getElementById('customer-street')?.value.trim();
        const city = document.getElementById('customer-city')?.value.trim();
        const state = document.getElementById('customer-state')?.value.trim();
        const pincode = document.getElementById('customer-pincode')?.value.trim();
        const paymentMethodEl = document.querySelector('input[name="payment_method"]:checked');
        const paymentMethod = paymentMethodEl ? paymentMethodEl.value : 'online';

        // Validations
        if (!name || name.length < 2) {
          window.showToast("Please enter your full name", "error");
          return;
        }

        const cleanPhone = phone.replace(/\D/g, '');
        if (cleanPhone.length < 10) {
          window.showToast("Please enter a valid 10-digit mobile number", "error");
          return;
        }

        if (!street || street.length < 5 || !city || cleanPhone.length !== 10) {
          window.showToast("Please complete your delivery address", "error");
          return;
        }

        if (!/^\d{6}$/.test(pincode)) {
          window.showToast("PIN code must be exactly 6 digits", "error");
          return;
        }

        // Disable button & indicate loading
        placeOrderBtn.disabled = true;
        const origBtnText = placeOrderBtn.textContent;
        placeOrderBtn.textContent = (paymentMethod === 'online') ? 'Connecting to Cashfree...' : 'Placing Order...';

        const payload = {
          name: name,
          phone: cleanPhone,
          email: email,
          street: street,
          city: city,
          state: state,
          pincode: pincode,
          payment_method: paymentMethod
        };

        fetch(window.SVG_APP.apiUrl + '/checkout.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        })
          .then(r => r.json())
          .then(res => {
            if (!res.success) {
              window.showToast(res.error || "Order creation failed", "error");
              placeOrderBtn.disabled = false;
              placeOrderBtn.textContent = origBtnText;
              return;
            }

            // If Cash on delivery or redirect returned
            if (paymentMethod === 'cod' || !res.payment_session_id) {
              window.location.href = res.redirect_url;
              return;
            }

            // Launch Cashfree Checkout Dropin
            try {
              const cfMode = (res.environment || 'PRODUCTION').toLowerCase();
              /* global Cashfree */
              const cashfree = Cashfree({
                mode: cfMode === 'production' ? 'production' : 'sandbox'
              });

              cashfree.checkout({
                paymentSessionId: res.payment_session_id,
                redirectTarget: "_self"
              }).then(function (result) {
                if (result.error) {
                  window.showToast(result.error.message || "Payment cancelled", "error");
                  placeOrderBtn.disabled = false;
                  placeOrderBtn.textContent = origBtnText;
                }
                if (result.redirect) {
                  // Handled by Cashfree SDK
                }
              });
            } catch (err) {
              console.error("Cashfree Launch Exception:", err);
              // Fallback redirect to order-success
              window.location.href = res.redirect_url;
            }
          })
          .catch(err => {
            console.error("Checkout request failed:", err);
            window.showToast("Network error creating order. Please try again.", "error");
            placeOrderBtn.disabled = false;
            placeOrderBtn.textContent = origBtnText;
          });
      });
    }
  });

})();
