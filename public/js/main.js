/**
 * SVG Dry Fruits - Storefront Interactivity & Animations
 * Faithfully matches Next.js Header, CollectionSection, CartToast, and Motion
 */

(function () {
  'use strict';

  // --- Cart Toast System ---
  window.triggerCartToast = function (payload) {
    var container = document.getElementById('cart-toast-container');
    if (!container) return;

    var toast = document.createElement('div');
    toast.className = 'cart-toast-card';
    var sizeText = payload.size ? ' · ' + payload.size : '';
    var qtyText = (payload.qty && payload.qty > 1) ? ' (×' + payload.qty + ')' : '';

    toast.innerHTML = `
      <div class="cart-toast-icon-wrap" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <path d="M16 10a4 4 0 0 1-8 0"></path>
        </svg>
      </div>
      <div class="cart-toast-content">
        <div class="cart-toast-header">
          <strong>Product added to bag!</strong>
          <span class="cart-toast-badge">In Bag</span>
        </div>
        <p class="cart-toast-title">${payload.title || 'Product'}${sizeText}${qtyText}</p>
        <div class="cart-toast-actions">
          <a href="${window.SVG_APP.baseUrl}cart" class="cart-toast-cta">
            View Bag &amp; Checkout <span>→</span>
          </a>
        </div>
      </div>
      <button type="button" class="cart-toast-close" aria-label="Dismiss notification">&times;</button>
    `;

    var closeBtn = toast.querySelector('.cart-toast-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', function () {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-16px) scale(0.94)';
        toast.style.transition = 'all 0.2s';
        setTimeout(function () { toast.remove(); }, 200);
      });
    }

    container.appendChild(toast);

    setTimeout(function () {
      if (toast.parentNode) {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-16px) scale(0.94)';
        toast.style.transition = 'all 0.25s';
        setTimeout(function () { toast.remove(); }, 250);
      }
    }, 4500);
  };

  // --- Add To Cart Operation ---
  window.addToCart = function (variantId, qty, meta) {
    if (!variantId) {
      alert("Please select a pack size.");
      return;
    }
    qty = qty || 1;

    fetch(window.SVG_APP.apiUrl + '/cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'add', variant_id: variantId, quantity: qty })
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.success) {
          var badge = document.getElementById('header-bag-count');
          if (badge) badge.textContent = res.count;

          window.triggerCartToast({
            title: (meta && meta.title) ? meta.title : 'Item',
            size: (meta && meta.size) ? meta.size : '',
            qty: qty
          });
        } else {
          alert(res.error || "Failed to add item to bag");
        }
      })
      .catch(function (err) {
        console.error("Cart error:", err);
      });
  };

  // --- Header Navigation & Search Toggles ---
  document.addEventListener('DOMContentLoaded', function () {
    var searchBtn = document.getElementById('search-toggle-btn');
    var searchPanel = document.getElementById('search-panel');
    var menuBtn = document.getElementById('menu-toggle-btn');
    var mobileNav = document.getElementById('mobile-nav');

    if (searchBtn && searchPanel) {
      searchBtn.addEventListener('click', function () {
        var isHidden = searchPanel.style.display === 'none';
        searchPanel.style.display = isHidden ? 'block' : 'none';
        if (mobileNav) mobileNav.style.display = 'none';
        if (isHidden) {
          var input = searchPanel.querySelector('input');
          if (input) setTimeout(function () { input.focus(); }, 50);
        }
      });
    }

    if (menuBtn && mobileNav) {
      menuBtn.addEventListener('click', function () {
        var isHidden = mobileNav.style.display === 'none';
        mobileNav.style.display = isHidden ? 'flex' : 'none';
        menuBtn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
        if (searchPanel) searchPanel.style.display = 'none';
      });
    }

    // --- Home Collection Tab Filtering ---
    var collTabs = document.querySelectorAll('.coll-tab-btn');
    var collCards = document.querySelectorAll('.coll-card');

    collTabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        collTabs.forEach(function (t) {
          t.classList.remove('is-active');
          t.setAttribute('aria-selected', 'false');
          var ind = t.querySelector('.coll-tab-indicator');
          if (ind) ind.style.display = 'none';
        });
        tab.classList.add('is-active');
        tab.setAttribute('aria-selected', 'true');
        var ind = tab.querySelector('.coll-tab-indicator');
        if (ind) ind.style.display = 'block';

        var cat = tab.getAttribute('data-category');
        collCards.forEach(function (card) {
          if (cat === 'all' || card.getAttribute('data-category') === cat) {
            card.style.display = 'flex';
          } else {
            card.style.display = 'none';
          }
        });
      });
    });

    // --- Motion / Scroll-Reveal (Matching Motion.tsx) ---
    if ("IntersectionObserver" in window && !window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
      document.documentElement.classList.add("motion-ready");
      var observer = new IntersectionObserver(function (entries, io) {
        entries.forEach(function (e) {
          if (e.isIntersecting) {
            e.target.classList.add("is-visible");
            io.unobserve(e.target);
          }
        });
      }, { threshold: 0.12 });

      document.querySelectorAll(".gifting-section, .story-section-v2, .coll-card, .eoc-card").forEach(function (el) {
        el.classList.add("motion-reveal");
        observer.observe(el);
      });
    }
  });

  // --- Home Collection Card Size Selection ---
  window.selectCardSize = function (btn) {
    var card = btn.closest('.coll-card');
    if (!card) return;

    var pills = card.querySelectorAll('.coll-size-pill');
    pills.forEach(function (p) {
      p.classList.remove('is-active');
      p.setAttribute('aria-checked', 'false');
    });
    btn.classList.add('is-active');
    btn.setAttribute('aria-checked', 'true');

    var priceEl = card.querySelector('.coll-card-price');
    if (priceEl) priceEl.textContent = btn.getAttribute('data-price');

    var compEl = card.querySelector('.coll-card-compare');
    var compVal = btn.getAttribute('data-compare');
    if (compVal) {
      if (!compEl) {
        compEl = document.createElement('s');
        compEl.className = 'coll-card-compare';
        priceEl.parentNode.appendChild(compEl);
      }
      compEl.textContent = compVal;
      compEl.style.display = 'inline';
    } else if (compEl) {
      compEl.style.display = 'none';
    }
  };

  // --- Card Quantity Stepper (- and +) ---
  window.adjustCardQty = function (btn, diff) {
    var card = btn.closest('.coll-card, .eoc-card');
    if (!card) return;
    var qtyVal = card.querySelector('.coll-card-qty-val, .eoc-card-qty-val');
    if (!qtyVal) return;
    var current = parseInt(qtyVal.textContent, 10) || 1;
    var next = Math.max(1, current + diff);
    qtyVal.textContent = next;
  };

  // --- Home Collection Card Add to Bag ---
  window.handleCardAdd = function (btn) {
    var card = btn.closest('.coll-card');
    if (!card) return;
    var activePill = card.querySelector('.coll-size-pill.is-active');
    if (!activePill) return;

    var variantId = activePill.getAttribute('data-variant-id');
    var variantLabel = activePill.getAttribute('data-label');
    var prodName = btn.getAttribute('data-product-name');
    var qtyVal = card.querySelector('.coll-card-qty-val');
    var qty = qtyVal ? (parseInt(qtyVal.textContent, 10) || 1) : 1;

    window.addToCart(variantId, qty, {
      title: prodName,
      size: variantLabel,
      qty: qty
    });
  };

  // --- Shop Catalog Card Size Selection ---
  window.selectEocCardSize = function (btn) {
    var card = btn.closest('.eoc-card');
    if (!card) return;

    var pills = card.querySelectorAll('.eoc-size-pill');
    pills.forEach(function (p) { p.classList.remove('is-selected'); });
    btn.classList.add('is-selected');

    var priceEl = card.querySelector('.eoc-card-price');
    if (priceEl) priceEl.textContent = btn.getAttribute('data-price');
  };

  // --- Shop Catalog Card Add to Bag ---
  window.handleEocCardAdd = function (btn) {
    var card = btn.closest('.eoc-card');
    if (!card) return;
    var activePill = card.querySelector('.eoc-size-pill.is-selected');
    if (!activePill) return;

    var variantId = activePill.getAttribute('data-variant-id');
    var variantLabel = activePill.getAttribute('data-label');
    var prodName = btn.getAttribute('data-product-name');
    var qtyVal = card.querySelector('.eoc-card-qty-val');
    var qty = qtyVal ? (parseInt(qtyVal.textContent, 10) || 1) : 1;

    window.addToCart(variantId, qty, {
      title: prodName,
      size: variantLabel,
      qty: qty
    });
  };

  // --- How It Works Interactive Steps Switcher ---
  window.switchStep = function (stepIdx) {
    var cols = document.querySelectorAll('.step-column-item');
    cols.forEach(function (col, idx) {
      var isCurrent = (idx === stepIdx);
      var badge = col.querySelector('.step-badge-wrap');
      var card = col.querySelector('.step-content-card');
      var actionRow = col.querySelector('.step-action-row');

      if (isCurrent) {
        col.classList.add('step-active-col');
        if (badge) {
          badge.classList.add('badge-active');
          if (!badge.querySelector('.badge-active-glow')) {
            var glow = document.createElement('span');
            glow.className = 'badge-active-glow';
            badge.appendChild(glow);
          }
        }
        if (card) card.classList.add('card-highlighted');
        if (actionRow) actionRow.style.display = 'block';
      } else {
        col.classList.remove('step-active-col');
        if (badge) {
          badge.classList.remove('badge-active');
          var glow = badge.querySelector('.badge-active-glow');
          if (glow) glow.remove();
        }
        if (card) card.classList.remove('card-highlighted');
        if (actionRow) actionRow.style.display = 'none';
      }
    });

    // Update Wave Milestone Beads
    var beads = document.querySelectorAll('.step-wave-bead');
    beads.forEach(function (b, bIdx) {
      if ((stepIdx === 0 && bIdx === 0) || (stepIdx === 1 && (bIdx === 0 || bIdx === 1)) || (stepIdx === 2 && (bIdx === 1 || bIdx === 2)) || (stepIdx === 3 && bIdx === 2)) {
        b.setAttribute('r', '5.5');
      } else {
        b.setAttribute('r', '4');
      }
    });
  };

})();
