"use client";

import Link from "next/link";
import { useEffect, useMemo, useState } from "react";
import { pruneMissing, setQuantity, useCart } from "@/lib/cart";
import { formatINR } from "@/lib/money";

type Priced = {
  lines: {
    variantId: string;
    productSlug: string;
    imageUrl: string | null;
    productName: string;
    variantLabel: string;
    unitPricePaise: number;
    quantity: number;
    available: number;
  }[];
  totals: {
    subtotalPaise: number;
    discountPaise: number;
    shippingPaise: number;
    totalPaise: number;
  };
  missing: string[];
  freeThresholdPaise: number | null;
  couponError: string | null;
};

const COUPON_KEY = "svg-coupon-v1";
const GIFT_NOTE_KEY = "svg-gift-note-v1";

export default function CartPage() {
  const cart = useCart();
  const [data, setData] = useState<Priced | null>(null);
  const [error, setError] = useState(false);
  const [coupon, setCoupon] = useState("");
  const [applied, setApplied] = useState("");
  const [showCouponInput, setShowCouponInput] = useState(false);
  const [showGiftNote, setShowGiftNote] = useState(false);
  const [giftNote, setGiftNote] = useState("");

  useEffect(() => {
    const saved = sessionStorage.getItem(COUPON_KEY) ?? "";
    setApplied(saved);
    setCoupon(saved);
    const savedNote = sessionStorage.getItem(GIFT_NOTE_KEY) ?? "";
    if (savedNote) {
      setGiftNote(savedNote);
      setShowGiftNote(true);
    }
  }, []);

  useEffect(() => {
    if (!cart.length) {
      setData(null);
      return;
    }
    let cancelled = false;
    fetch("/api/cart/validate", {
      method: "POST",
      headers: { "content-type": "application/json" },
      body: JSON.stringify({
        lines: cart.map((l) => ({ variantId: l.variantId, quantity: l.quantity })),
        couponCode: applied || undefined,
      }),
    })
      .then((r) => (r.ok ? r.json() : Promise.reject()))
      .then((d) => {
        if (!cancelled) {
          setData(d);
          setError(false);
          if (d.missing && d.missing.length > 0) {
            pruneMissing(d.missing);
          }
        }
      })
      .catch(() => {
        if (!cancelled) setError(true);
      });
    return () => {
      cancelled = true;
    };
  }, [cart, applied]);

  function applyCoupon(e: React.FormEvent) {
    e.preventDefault();
    const code = coupon.trim();
    if (code) sessionStorage.setItem(COUPON_KEY, code);
    else sessionStorage.removeItem(COUPON_KEY);
    setApplied(code);
  }

  function removeCoupon() {
    sessionStorage.removeItem(COUPON_KEY);
    setApplied("");
    setCoupon("");
  }

  function handleSaveGiftNote(val: string) {
    setGiftNote(val);
    if (val.trim()) {
      sessionStorage.setItem(GIFT_NOTE_KEY, val.trim());
    } else {
      sessionStorage.removeItem(GIFT_NOTE_KEY);
    }
  }

  // Value Proposition Features Strip Component
  const featuresBanner = (
    <div className="cart-v3-features-banner">
      <div className="cart-v3-feature-item">
        <div className="cart-v3-feat-icon-circle">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#2A382C" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round">
            <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z" />
            <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12" />
          </svg>
        </div>
        <div className="cart-v3-feat-info">
          <span className="cart-v3-feat-title">Premium Quality</span>
          <span className="cart-v3-feat-desc">Sourced from trusted farms</span>
        </div>
      </div>

      <div className="cart-v3-feature-item">
        <div className="cart-v3-feat-icon-circle">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#2A382C" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round">
            <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
            <path d="m3.3 7 8.7 5 8.7-5" />
            <path d="M12 22V12" />
          </svg>
        </div>
        <div className="cart-v3-feat-info">
          <span className="cart-v3-feat-title">Safe Packaging</span>
          <span className="cart-v3-feat-desc">Freshness in every pack</span>
        </div>
      </div>

      <div className="cart-v3-feature-item">
        <div className="cart-v3-feat-icon-circle">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#2A382C" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round">
            <rect x="1" y="3" width="15" height="13" />
            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8" />
            <circle cx="5.5" cy="18.5" r="2.5" />
            <circle cx="18.5" cy="18.5" r="2.5" />
          </svg>
        </div>
        <div className="cart-v3-feat-info">
          <span className="cart-v3-feat-title">Fast Delivery</span>
          <span className="cart-v3-feat-desc">Pan India</span>
        </div>
      </div>

      <div className="cart-v3-feat-script">
        <span className="cart-v3-feat-script-text">Nourishment for a Brighter You</span>
        <span className="cart-v3-feat-script-line" />
      </div>
    </div>
  );

  // Empty State
  if (!cart.length) {
    return (
      <div className="cart-v3-page-wrapper">
        <div className="cart-v3-container">
          {/* Hero Header */}
          <div className="cart-v3-hero">
            <div className="cart-v3-hero-left">
              <span className="cart-v3-tag">YOUR CART</span>
              <h1 className="cart-v3-title">
                Good Choices<br />
                Go a Long Way
              </h1>
              <p className="cart-v3-subtitle">
                Wholesome dry fruits, closer to a healthier you.<br />
                Review your cart and continue to bring home nature&apos;s best.
              </p>
            </div>
            <div className="cart-v3-hero-right">
              <div className="cart-v3-hero-img-box">
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img
                  src="/assets/cart-hero-bowl-isolated.png"
                  alt="Assorted artisanal dry fruits in wooden bowl"
                  className="cart-v3-hero-img"
                />
              </div>
              <div className="cart-v3-hero-script">
                <span className="cart-v3-script-text">
                  Real Ingredients.<br />
                  Brighter Days.
                </span>
                <span className="cart-v3-script-line" />
              </div>
            </div>
          </div>

          <div className="cart-v3-empty-card">
            <div className="cart-v3-empty-icon">
              <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round">
                <path d="M5.5 8.5h13l1 12h-15l1-12Z" />
                <path d="M9 9V6a3 3 0 0 1 6 0v3" />
              </svg>
            </div>
            <h2 className="cart-v3-empty-title">Your Cart is Empty</h2>
            <p className="cart-v3-empty-desc">
              Discover our handpicked California almonds, crunchy cashews, roasted pistachios, and pure harvests.
            </p>
            <Link href="/shop" className="cart-v3-empty-btn">
              <span>Explore Our Collection</span>
              <span aria-hidden="true">→</span>
            </Link>
          </div>

          {featuresBanner}
        </div>
      </div>
    );
  }

  // Error State
  if (error) {
    return (
      <div className="cart-v3-page-wrapper">
        <div className="cart-v3-container">
          <div style={{ padding: "40px 20px", textAlign: "center", background: "#FFFFFF", borderRadius: 16, border: "1px solid #ECE7DE" }}>
            <p style={{ color: "#9B1C1C", margin: "0 0 16px" }}>
              We couldn&apos;t refresh your cart at this moment. Please refresh the page.
            </p>
            <button
              type="button"
              onClick={() => window.location.reload()}
              className="cart-v3-empty-btn"
            >
              Refresh Page
            </button>
          </div>
        </div>
      </div>
    );
  }

  const optimisticData: Priced | null = useMemo(() => {
    if (!cart.length) return null;
    const subtotal = cart.reduce((sum, l) => sum + (l.unitPricePaise || 0) * l.quantity, 0);
    return {
      lines: cart.map((l) => ({
        variantId: l.variantId,
        productSlug: "",
        imageUrl: l.imageUrl || null,
        productName: l.productName || "Product Item",
        variantLabel: l.variantLabel || "",
        unitPricePaise: l.unitPricePaise || 0,
        quantity: l.quantity,
        available: 99,
      })),
      totals: {
        subtotalPaise: subtotal,
        discountPaise: 0,
        shippingPaise: subtotal >= 100000 || subtotal === 0 ? 0 : 5000,
        totalPaise: subtotal >= 100000 || subtotal === 0 ? subtotal : subtotal + 5000,
      },
      missing: [],
      freeThresholdPaise: 100000,
      couponError: null,
    };
  }, [cart]);

  const activeData = data || optimisticData;

  // Loading State only if neither server data nor optimistic data exists
  if (!activeData) {
    return (
      <div className="cart-v3-page-wrapper">
        <div className="cart-v3-container">
          <div className="cart-v3-hero">
            <div className="cart-v3-hero-left">
              <span className="cart-v3-tag">YOUR CART</span>
              <h1 className="cart-v3-title">
                Good Choices<br />
                Go a Long Way
              </h1>
            </div>
          </div>
          <div style={{ textAlign: "center", padding: "60px 20px", color: "#6B6255" }}>
            Loading cart…
          </div>
        </div>
      </div>
    );
  }

  const itemCount = activeData.lines.reduce((s, l) => s + l.quantity, 0);
  const subtotal = activeData.totals.subtotalPaise;
  const shippingPaise = activeData.totals.shippingPaise;
  const discountPaise = activeData.totals.discountPaise;
  const totalPaise = activeData.totals.totalPaise;

  return (
    <div className="cart-v3-page-wrapper">
      <div className="cart-v3-container">
        {/* Hero Section */}
        <div className="cart-v3-hero">
          <div className="cart-v3-hero-left">
            <span className="cart-v3-tag">YOUR CART</span>
            <h1 className="cart-v3-title">
              Good Choices<br />
              Go a Long Way
            </h1>
            <p className="cart-v3-subtitle">
              Wholesome dry fruits, closer to a healthier you.<br />
              Review your cart and continue to bring home nature&apos;s best.
            </p>
          </div>

          <div className="cart-v3-hero-right">
            <div className="cart-v3-hero-img-box">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img
                src="/assets/cart-hero-bowl-isolated.png"
                alt="Natural dry fruits in artisanal wooden bowl on linen"
                className="cart-v3-hero-img"
              />
            </div>
            <div className="cart-v3-hero-script">
              <span className="cart-v3-script-text">
                Real Ingredients.<br />
                Brighter Days.
              </span>
              <span className="cart-v3-script-line" />
            </div>
          </div>
        </div>

        {/* 2-Column Cart Grid */}
        <div className="cart-v3-grid">
          {/* Left Column: Cart Items Card */}
          <div className="cart-v3-items-card">
            {/* Table Header */}
            <div className="cart-v3-table-head">
              <div className="cart-v3-th-product">PRODUCT</div>
              <div className="cart-v3-th-price">PRICE</div>
              <div className="cart-v3-th-qty">QUANTITY</div>
              <div className="cart-v3-th-total">TOTAL</div>
            </div>

            {/* Table Body */}
            <div className="cart-v3-table-body">
              {activeData.lines.map((l) => (
                <div key={l.variantId} className="cart-v3-item-row">
                  {/* PRODUCT */}
                  <div className="cart-v3-cell-product">
                    <Link href={`/products/${l.productSlug}`} className="cart-v3-thumb-link">
                      {/* eslint-disable-next-line @next/next/no-img-element */}
                      <img
                        src={l.imageUrl ?? "/assets/shop-pistachios-hd.jpg"}
                        alt={l.productName}
                        className="cart-v3-thumb-img"
                      />
                    </Link>
                    <div className="cart-v3-product-details">
                      <Link href={`/products/${l.productSlug}`} className="cart-v3-product-name">
                        {l.productName}
                      </Link>
                      <span className="cart-v3-variant-label">{l.variantLabel}</span>
                      <button
                        type="button"
                        onClick={() => setQuantity(l.variantId, 0)}
                        className="cart-v3-remove-btn"
                        aria-label={`Remove ${l.productName} from cart`}
                      >
                        <svg
                          viewBox="0 0 24 24"
                          width="13"
                          height="13"
                          fill="none"
                          stroke="currentColor"
                          strokeWidth="1.8"
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          aria-hidden="true"
                        >
                          <polyline points="3 6 5 6 21 6" />
                          <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                          <line x1="10" y1="11" x2="10" y2="17" />
                          <line x1="14" y1="11" x2="14" y2="17" />
                        </svg>
                        <span>Remove</span>
                      </button>
                    </div>
                  </div>

                  {/* PRICE */}
                  <div className="cart-v3-cell-price">
                    {formatINR(l.unitPricePaise)}
                  </div>

                  {/* QUANTITY */}
                  <div className="cart-v3-cell-qty">
                    <div className="cart-v3-stepper">
                      <button
                        type="button"
                        onClick={() => setQuantity(l.variantId, l.quantity - 1)}
                        className="cart-v3-step-btn"
                        aria-label={`Decrease quantity of ${l.productName}`}
                      >
                        −
                      </button>
                      <span className="cart-v3-step-val">{l.quantity}</span>
                      <button
                        type="button"
                        onClick={() => setQuantity(l.variantId, l.quantity + 1)}
                        className="cart-v3-step-btn"
                        aria-label={`Increase quantity of ${l.productName}`}
                      >
                        +
                      </button>
                    </div>
                  </div>

                  {/* TOTAL */}
                  <div className="cart-v3-cell-total">
                    {formatINR(l.unitPricePaise * l.quantity)}
                  </div>
                </div>
              ))}
            </div>

            {/* Subtle Collapsible Extras: Gifting Note */}
            <div className="cart-v3-extras">
              {/* Complimentary Gifting Note Toggle */}
              <div>
                <button
                  type="button"
                  onClick={() => setShowGiftNote(!showGiftNote)}
                  className="cart-v3-extra-toggle"
                >
                  <span>{giftNote ? "✓ Gifting note attached" : "+ Add complimentary handwritten gift note"}</span>
                </button>

                {showGiftNote && (
                  <div style={{ marginTop: 8 }}>
                    <textarea
                      value={giftNote}
                      onChange={(e) => handleSaveGiftNote(e.target.value)}
                      placeholder="Write your personal message for the recipient (complimentary handwritten note included with festive dry fruits box)..."
                      maxLength={240}
                      rows={2}
                      style={{
                        width: "100%",
                        maxWidth: 420,
                        border: "1px solid #D8D2C5",
                        borderRadius: 6,
                        padding: "8px 12px",
                        fontSize: 13,
                        background: "#FAF7F2",
                        fontFamily: "inherit",
                        resize: "vertical",
                      }}
                    />
                    <div style={{ fontSize: 11, color: "#8C8477", marginTop: 2 }}>
                      {240 - giftNote.length} characters remaining
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* Right Column: Order Summary Card */}
          <div className="cart-v3-summary-card">
            <h2 className="cart-v3-summary-title">ORDER SUMMARY</h2>

            <div className="cart-v3-summary-rows">
              <div className="cart-v3-summary-row">
                <span>Subtotal ({itemCount} {itemCount === 1 ? "item" : "items"})</span>
                <span className="cart-v3-summary-val">{formatINR(subtotal)}</span>
              </div>

              <div className="cart-v3-summary-row">
                <span>Shipping</span>
                <span
                  className="cart-v3-summary-val"
                  style={{ color: shippingPaise === 0 ? "#2E7D32" : "inherit", fontWeight: 600 }}
                >
                  {shippingPaise === 0 ? "Free" : formatINR(shippingPaise)}
                </span>
              </div>

              <div className="cart-v3-summary-row">
                <span>Estimated Taxes</span>
                <span className="cart-v3-summary-val">₹0</span>
              </div>

              {/* Promo code placed directly below Estimated Taxes */}
              <div className="cart-v3-summary-promo-box">
                {applied ? (
                  <div className="cart-v3-summary-applied-coupon">
                    <div style={{ display: "flex", alignItems: "center", gap: 6 }}>
                      <span style={{ fontSize: 13, color: "#2E7D32", fontWeight: 600 }}>🏷️ {applied.toUpperCase()}</span>
                      <span style={{ fontSize: 11.5, color: "#2E7D32" }}>(Applied)</span>
                    </div>
                    <button
                      type="button"
                      onClick={removeCoupon}
                      className="cart-v3-promo-remove-btn"
                    >
                      Remove
                    </button>
                  </div>
                ) : (
                  <div>
                    {!showCouponInput ? (
                      <button
                        type="button"
                        onClick={() => setShowCouponInput(true)}
                        className="cart-v3-summary-promo-toggle"
                      >
                        + Have a promo code?
                      </button>
                    ) : (
                      <form onSubmit={applyCoupon} className="cart-v3-summary-promo-form">
                        <input
                          type="text"
                          value={coupon}
                          onChange={(e) => setCoupon(e.target.value)}
                          placeholder="Enter promo code"
                          className="cart-v3-summary-promo-input"
                          autoFocus
                        />
                        <button type="submit" className="cart-v3-summary-promo-btn">
                          Apply
                        </button>
                      </form>
                    )}
                  </div>
                )}
                {activeData.couponError && (
                  <p style={{ color: "#9B1C1C", fontSize: 11.5, margin: "4px 0 0" }}>
                    {activeData.couponError}
                  </p>
                )}
              </div>

              {discountPaise > 0 && (
                <div className="cart-v3-summary-row" style={{ color: "#2E7D32" }}>
                  <span>Coupon Discount</span>
                  <span className="cart-v3-summary-val">−{formatINR(discountPaise)}</span>
                </div>
              )}
            </div>

            <div className="cart-v3-summary-divider" />

            <div className="cart-v3-total-row">
              <span className="cart-v3-total-label">Total</span>
              <div className="cart-v3-total-right">
                <span className="cart-v3-total-price">{formatINR(totalPaise)}</span>
                <span className="cart-v3-tax-note">Inclusive of all taxes</span>
              </div>
            </div>

            <Link href="/checkout" className="cart-v3-checkout-btn">
              <span>Proceed to Checkout</span>
              <span aria-hidden="true">→</span>
            </Link>

            <div className="cart-v3-secure-badge">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                <path d="m9 12 2 2 4-4" />
              </svg>
              <span>100% Secure Payments</span>
            </div>

            <div className="cart-v3-payment-icons">
              <span className="cart-v3-pay-pill">UPI</span>
              <span className="cart-v3-pay-pill">VISA</span>
              <span className="cart-v3-pay-pill">Mastercard</span>
              <span className="cart-v3-pay-pill">RuPay</span>
              <span className="cart-v3-pay-pill">Amex</span>
            </div>

            {/* Free Shipping Highlight Box */}
            <div className="cart-v3-free-ship-card">
              <div className="cart-v3-free-ship-icon">
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img
                  src="/assets/cart-botanical-branch.png"
                  alt=""
                  style={{ width: "100%", height: "100%", objectFit: "contain" }}
                />
              </div>
              <div className="cart-v3-free-ship-text">
                <span className="cart-v3-free-ship-title">Free Shipping</span>
                <span className="cart-v3-free-ship-desc">on orders above ₹999</span>
                <span className="cart-v3-free-ship-rule" />
              </div>
            </div>
          </div>
        </div>

        {/* Bottom Features Banner */}
        {featuresBanner}
      </div>
    </div>
  );
}
