"use client";

import Link from "next/link";
import Script from "next/script";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { clearCart, pruneMissing, useCart } from "@/lib/cart";
import { formatINR } from "@/lib/money";
import { Icon } from "@/components/Icon";
import { loadRazorpayScript } from "@/lib/razorpay-client";

type PricedCart = {
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
  couponError: string | null;
};

const COUPON_KEY = "svg-coupon-v1";

export default function CheckoutPage() {
  const router = useRouter();
  const cart = useCart();
  const [data, setData] = useState<PricedCart | null>(null);
  const [loadingCart, setLoadingCart] = useState(true);
  const [coupon, setCoupon] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [scriptLoaded, setScriptLoaded] = useState(false);
  const [mockRazorpayModal, setMockRazorpayModal] = useState<{
    orderId: string;
    orderNumber: string;
    trackingToken: string;
    razorpayOrderId: string;
    amountPaise: number;
    currency: string;
  } | null>(null);
  const [mockApproving, setMockApproving] = useState(false);
  const [mockMethod, setMockMethod] = useState<"upi" | "card" | "netbanking">("upi");

  // Guest Customer Information
  const [form, setForm] = useState({
    name: "",
    phone: "",
    email: "",
    pincode: "",
    city: "",
    state: "",
    street: "",
    landmark: "",
  });

  useEffect(() => {
    const savedCoupon = sessionStorage.getItem(COUPON_KEY) ?? "";
    setCoupon(savedCoupon);
  }, []);

  useEffect(() => {
    if (!cart.length) {
      setLoadingCart(false);
      return;
    }
    let cancelled = false;
    fetch("/api/cart/validate", {
      method: "POST",
      headers: { "content-type": "application/json" },
      body: JSON.stringify({ lines: cart, couponCode: coupon || undefined }),
    })
      .then((res) => (res.ok ? res.json() : Promise.reject()))
      .then((d) => {
        if (!cancelled) {
          if (d.missing && d.missing.length > 0) {
            pruneMissing(d.missing);
          }
          setData(d);
          setLoadingCart(false);
        }
      })
      .catch(() => {
        if (!cancelled) {
          setError("Unable to validate bag totals. Please refresh.");
          setLoadingCart(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [cart, coupon]);

  function handleChange(e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);

    // Validation
    if (!form.name.trim() || form.name.trim().length < 2) {
      setError("Please enter your full name.");
      return;
    }
    if (!/^[6-9]\d{9}$/.test(form.phone.trim())) {
      setError("Please enter a valid 10-digit Indian mobile number (e.g. 9876543210).");
      return;
    }
    if (!/^\d{6}$/.test(form.pincode.trim())) {
      setError("Please enter a valid 6-digit Indian PIN code.");
      return;
    }
    if (!form.city.trim()) {
      setError("Please enter your city / town.");
      return;
    }
    if (!form.state.trim()) {
      setError("Please select or enter your state.");
      return;
    }
    if (!form.street.trim() || form.street.trim().length < 5) {
      setError("Please enter your complete street address (flat/house no, street).");
      return;
    }

    setSubmitting(true);

    try {
      const res = await fetch("/api/checkout", {
        method: "POST",
        headers: { "content-type": "application/json" },
        body: JSON.stringify({
          customer: {
            name: form.name.trim(),
            phone: form.phone.trim(),
            email: form.email.trim() || undefined,
            address: {
              street: form.street.trim(),
              landmark: form.landmark.trim() || undefined,
              city: form.city.trim(),
              state: form.state.trim(),
              pincode: form.pincode.trim(),
            },
          },
          lines: cart,
          couponCode: coupon.trim() || undefined,
        }),
      });

      const orderData = await res.json();
      if (!res.ok) {
        setError(orderData.error || "Failed to create checkout. Please try again.");
        setSubmitting(false);
        return;
      }

      // Check if test / mock mode (e.g. when Razorpay credentials aren't set in dev environment)
      if (orderData.isMock) {
        setMockRazorpayModal(orderData);
        setSubmitting(false);
        return;
      }

      // Live / Sandbox Razorpay checkout
      const scriptReady = await loadRazorpayScript();
      if (typeof window !== "undefined" && (window as unknown as { Razorpay?: unknown }).Razorpay) {
        const RazorpayClass = (
          window as unknown as {
            Razorpay: new (opts: unknown) => {
              open: () => void;
              on: (event: string, callback: (res: any) => void) => void;
            };
          }
        ).Razorpay;
        const rzp = new RazorpayClass({
          key: orderData.keyId,
          amount: orderData.amountPaise,
          currency: "INR",
          name: "SVG Dry Fruits",
          description: `Order ${orderData.orderNumber}`,
          image: "/assets/svg-dry-fruits-logo.jpg",
          order_id: orderData.razorpayOrderId,
          handler: async function (response: {
            razorpay_order_id: string;
            razorpay_payment_id: string;
            razorpay_signature: string;
          }) {
            const vRes = await fetch("/api/checkout/verify", {
              method: "POST",
              headers: { "content-type": "application/json" },
              body: JSON.stringify({
                orderId: orderData.orderId,
                razorpayOrderId: response.razorpay_order_id,
                razorpayPaymentId: response.razorpay_payment_id,
                razorpaySignature: response.razorpay_signature,
              }),
            });

            if (vRes.ok) {
              clearCart();
              sessionStorage.removeItem(COUPON_KEY);
              router.push(`/order/success?orderNumber=${encodeURIComponent(orderData.orderNumber)}&token=${encodeURIComponent(orderData.trackingToken)}`);
            } else {
              const vErr = await vRes.json();
              setError(vErr.error || "Payment received but order verification failed. Please contact us.");
              setSubmitting(false);
            }
          },
          prefill: {
            name: form.name.trim(),
            email: form.email.trim(),
            contact: form.phone.trim(),
          },
          theme: {
            color: "#203c32",
          },
          modal: {
            ondismiss: function () {
              setSubmitting(false);
            },
          },
        });

        rzp.on("payment.failed", function (response: any) {
          setSubmitting(false);
          const failureReason =
            response?.error?.description ||
            response?.error?.reason ||
            "Payment failed or was declined. Please try again with another payment method.";
          setError(failureReason);
        });

        rzp.open();
      } else {
        setError("Unable to load Razorpay payment gateway. Please check your internet connection or ad-blocker and try again.");
        setSubmitting(false);
      }
    } catch (err: unknown) {
      console.error(err);
      setError("An unexpected error occurred. Please try again.");
      setSubmitting(false);
    }
  }

  async function handleApproveMockPayment() {
    if (!mockRazorpayModal) return;
    setMockApproving(true);
    try {
      const verifyRes = await fetch("/api/checkout/verify", {
        method: "POST",
        headers: { "content-type": "application/json" },
        body: JSON.stringify({
          orderId: mockRazorpayModal.orderId,
          razorpayOrderId: mockRazorpayModal.razorpayOrderId,
          razorpayPaymentId: `pay_sim_${Date.now()}`,
          razorpaySignature: "mock_signature_approved",
        }),
      });

      if (verifyRes.ok) {
        clearCart();
        sessionStorage.removeItem(COUPON_KEY);
        router.push(
          `/order/success?orderNumber=${encodeURIComponent(
            mockRazorpayModal.orderNumber
          )}&token=${encodeURIComponent(mockRazorpayModal.trackingToken)}`
        );
      } else {
        const vErr = await verifyRes.json();
        setError(vErr.error || "Payment verification failed.");
        setMockRazorpayModal(null);
      }
    } catch {
      setError("Error finalizing payment.");
      setMockRazorpayModal(null);
    } finally {
      setMockApproving(false);
    }
  }

  if (loadingCart) {
    return (
      <section className="checkout-page">
        <div className="checkout-head">
          <div className="breadcrumbs"><Link href="/">Home</Link><span aria-hidden="true">/</span><Link href="/cart">Bag</Link><span aria-hidden="true">/</span><span>Checkout</span></div>
          <h1>Guest <em>checkout.</em></h1>
        </div>
        <p className="checkout-status" role="status">Loading checkout details…</p>
      </section>
    );
  }

  if (!cart.length || !data || data.lines.length === 0) {
    return (
      <section className="checkout-page">
        <div className="checkout-head">
          <div className="breadcrumbs"><Link href="/">Home</Link><span aria-hidden="true">/</span><span>Checkout</span></div>
          <h1>Guest <em>checkout.</em></h1>
        </div>
        <div className="cart-empty">
          <Icon name="bag" size={48} />
          <h2>Your bag is empty</h2>
          <p>Please add some dry fruits to your bag before checking out.</p>
          <Link className="button button-primary" href="/shop">Browse the collection <span aria-hidden="true">↗</span></Link>
        </div>
      </section>
    );
  }

  const unavailable = data.lines.some((l) => l.available < l.quantity) || data.missing.length > 0;

  return (
    <>
      <Script
        src="https://checkout.razorpay.com/v1/checkout.js"
        onLoad={() => setScriptLoaded(true)}
      />

      <section className="checkout-page">
        <div className="checkout-head">
          <div className="breadcrumbs">
            <Link href="/">Home</Link>
            <span aria-hidden="true">/</span>
            <Link href="/cart">Bag</Link>
            <span aria-hidden="true">/</span>
            <span>Checkout</span>
          </div>
          <h1>Guest <em>checkout.</em></h1>
          <p className="checkout-kicker">No account needed. Enter your delivery address to proceed with secure payment.</p>
        </div>

        {error && (
          <div className="checkout-error-banner" role="alert">
            <Icon name="shield" size={20} />
            <p>{error}</p>
          </div>
        )}

        <div className="checkout-grid">
          {/* Left column: Guest customer and shipping form */}
          <form className="checkout-form" onSubmit={handleSubmit}>
            <div className="checkout-section-box">
              <h2>1. Contact details</h2>
              <div className="form-group-row">
                <div className="form-group">
                  <label htmlFor="name">Full name <span className="req">*</span></label>
                  <input
                    id="name"
                    name="name"
                    required
                    value={form.name}
                    onChange={handleChange}
                    placeholder="e.g. Ramesh Kumar"
                    autoComplete="name"
                  />
                </div>
                <div className="form-group">
                  <label htmlFor="phone">Mobile number <span className="req">*</span></label>
                  <div className="phone-input-wrap">
                    <span className="phone-prefix">+91</span>
                    <input
                      id="phone"
                      name="phone"
                      type="tel"
                      required
                      value={form.phone}
                      onChange={handleChange}
                      placeholder="9876543210"
                      maxLength={10}
                      autoComplete="tel"
                    />
                  </div>
                </div>
              </div>
              <div className="form-group">
                <label htmlFor="email">Email address <span className="opt">(optional)</span></label>
                <input
                  id="email"
                  name="email"
                  type="email"
                  value={form.email}
                  onChange={handleChange}
                  placeholder="ramesh@example.com"
                  autoComplete="email"
                />
                <span className="field-hint">We’ll send order tracking updates if provided.</span>
              </div>
            </div>

            <div className="checkout-section-box">
              <h2>2. Delivery address</h2>
              <div className="form-group">
                <label htmlFor="street">Street address / Flat / House no <span className="req">*</span></label>
                <input
                  id="street"
                  name="street"
                  required
                  value={form.street}
                  onChange={handleChange}
                  placeholder="Flat 402, Lotus Residency, MG Road"
                  autoComplete="street-address"
                />
              </div>

              <div className="form-group">
                <label htmlFor="landmark">Landmark <span className="opt">(optional)</span></label>
                <input
                  id="landmark"
                  name="landmark"
                  value={form.landmark}
                  onChange={handleChange}
                  placeholder="Near City Hospital"
                />
              </div>

              <div className="form-group-row three-col">
                <div className="form-group">
                  <label htmlFor="pincode">PIN code <span className="req">*</span></label>
                  <input
                    id="pincode"
                    name="pincode"
                    required
                    maxLength={6}
                    value={form.pincode}
                    onChange={handleChange}
                    placeholder="500001"
                    autoComplete="postal-code"
                  />
                </div>
                <div className="form-group">
                  <label htmlFor="city">City / Town <span className="req">*</span></label>
                  <input
                    id="city"
                    name="city"
                    required
                    value={form.city}
                    onChange={handleChange}
                    placeholder="Hyderabad"
                    autoComplete="address-level2"
                  />
                </div>
                <div className="form-group">
                  <label htmlFor="state">State <span className="req">*</span></label>
                  <input
                    id="state"
                    name="state"
                    required
                    value={form.state}
                    onChange={handleChange}
                    placeholder="Telangana"
                    autoComplete="address-level1"
                  />
                </div>
              </div>
            </div>

            <div className="checkout-section-box payment-section-box">
              <h2>3. Payment method</h2>
              <div className="payment-method-card">
                <div className="payment-method-header">
                  <Icon name="card" size={24} />
                  <strong>Razorpay Secure Online Payment</strong>
                </div>
                <p>Pay securely using UPI (Google Pay, PhonePe, Paytm), NetBanking, Credit or Debit Cards. Cash on delivery is not offered.</p>
              </div>

              <button
                type="submit"
                className="button button-primary checkout-submit-btn"
                disabled={submitting || unavailable}
              >
                {submitting ? "Processing payment…" : unavailable ? "Fix unavailable items" : `Pay ${formatINR(data.totals.totalPaise)} with Razorpay`}
              </button>

              <p className="checkout-guarantee">
                <Icon name="shield" size={16} /> 256-bit encrypted gateway. Your card details are never stored on our server.
              </p>
            </div>
          </form>

          {/* Right column: Order Summary */}
          <aside className="checkout-summary" aria-label="Items in this order">
            <h2>Order summary</h2>
            <ul className="checkout-item-list">
              {data.lines.map((l) => (
                <li key={l.variantId} className="checkout-item-row">
                  <div className="checkout-item-thumb">
                    {/* eslint-disable-next-line @next/next/no-img-element */}
                    <img src={l.imageUrl ?? "/assets/mixed-nuts-reference.jpg"} alt="" />
                    <span className="checkout-item-badge">{l.quantity}</span>
                  </div>
                  <div className="checkout-item-meta">
                    <strong>{l.productName}</strong>
                    <span>{l.variantLabel}</span>
                    {l.available < l.quantity && (
                      <span className="cart-warn" role="alert">
                        {l.available > 0 ? `Only ${l.available} in stock.` : "Out of stock."}
                      </span>
                    )}
                  </div>
                  <div className="checkout-item-price">
                    {formatINR(l.unitPricePaise * l.quantity)}
                  </div>
                </li>
              ))}
            </ul>

            <dl className="checkout-totals">
              <div>
                <dt>Subtotal</dt>
                <dd>{formatINR(data.totals.subtotalPaise)}</dd>
              </div>
              {data.totals.discountPaise > 0 && (
                <div className="discount-row">
                  <dt>Discount ({coupon})</dt>
                  <dd>−{formatINR(data.totals.discountPaise)}</dd>
                </div>
              )}
              <div>
                <dt>Shipping</dt>
                <dd>{data.totals.shippingPaise ? formatINR(data.totals.shippingPaise) : "Free"}</dd>
              </div>
              <div className="checkout-grand-total">
                <dt>Total payable</dt>
                <dd>{formatINR(data.totals.totalPaise)}</dd>
              </div>
            </dl>

            <div className="checkout-back-link">
              <Link href="/cart">← Edit bag or coupon</Link>
            </div>
          </aside>
        </div>

        {/* =====================================================================
            RAZORPAY PAYMENT GATEWAY MODAL (TEST SIMULATOR & GATEWAY PREVIEW)
            ===================================================================== */}
        {mockRazorpayModal && (
          <div
            style={{
              position: "fixed",
              top: 0,
              left: 0,
              right: 0,
              bottom: 0,
              backgroundColor: "rgba(0, 0, 0, 0.65)",
              backdropFilter: "blur(4px)",
              zIndex: 99999,
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              padding: 16,
            }}
          >
            <div
              style={{
                width: "100%",
                maxWidth: 440,
                background: "#FFFFFF",
                borderRadius: 16,
                overflow: "hidden",
                boxShadow: "0 20px 50px rgba(0, 0, 0, 0.3)",
                animation: "fadeIn 0.2s ease-out",
                fontFamily: "system-ui, -apple-system, sans-serif",
              }}
            >
              {/* Razorpay Branded Topbar */}
              <div
                style={{
                  background: "#0c2340",
                  color: "#FFFFFF",
                  padding: "18px 20px",
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "space-between",
                }}
              >
                <div style={{ display: "flex", alignItems: "center", gap: 10 }}>
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img
                    src="/assets/svg-dry-fruits-logo.jpg"
                    alt=""
                    style={{ width: 36, height: 36, borderRadius: "50%", objectFit: "cover" }}
                  />
                  <div>
                    <strong style={{ display: "block", fontSize: 15, letterSpacing: 0.2 }}>
                      SVG Dry Fruits
                    </strong>
                    <span style={{ fontSize: 11, opacity: 0.75 }}>
                      Order {mockRazorpayModal.orderNumber}
                    </span>
                  </div>
                </div>
                <div style={{ textAlign: "right" }}>
                  <span style={{ display: "block", fontSize: 11, opacity: 0.75 }}>Payable</span>
                  <strong style={{ fontSize: 18, color: "#58c977" }}>
                    {formatINR(mockRazorpayModal.amountPaise)}
                  </strong>
                </div>
              </div>

              {/* Developer Notice */}
              <div
                style={{
                  padding: "10px 16px",
                  background: "#f0fdf4",
                  borderBottom: "1px solid #dcfce7",
                  fontSize: 11.5,
                  color: "#166534",
                  lineHeight: 1.4,
                  display: "flex",
                  alignItems: "center",
                  gap: 8,
                }}
              >
                <span>⚡</span>
                <span>
                  <strong>Razorpay Test Sandbox:</strong> Select any payment method and click approve to simulate an authentic payment.
                </span>
              </div>

              {/* Payment Methods */}
              <div style={{ padding: "16px 20px" }}>
                <span
                  style={{
                    display: "block",
                    fontSize: 12,
                    fontWeight: 700,
                    textTransform: "uppercase",
                    letterSpacing: 0.5,
                    color: "#64748b",
                    marginBottom: 10,
                  }}
                >
                  Select Payment Option
                </span>

                <div style={{ display: "flex", flexDirection: "column", gap: 8 }}>
                  {/* UPI Option */}
                  <label
                    style={{
                      display: "flex",
                      alignItems: "center",
                      gap: 12,
                      padding: "12px 14px",
                      borderRadius: 10,
                      border: `1.5px solid ${mockMethod === "upi" ? "#0c2340" : "#e2e8f0"}`,
                      background: mockMethod === "upi" ? "#f8fafc" : "#fff",
                      cursor: "pointer",
                      transition: "all 0.15s ease",
                    }}
                  >
                    <input
                      type="radio"
                      name="mockMethod"
                      checked={mockMethod === "upi"}
                      onChange={() => setMockMethod("upi")}
                    />
                    <div style={{ flex: 1 }}>
                      <strong style={{ display: "block", fontSize: 13, color: "#1e293b" }}>
                        UPI / QR Code
                      </strong>
                      <span style={{ fontSize: 11, color: "#64748b" }}>
                        Google Pay, PhonePe, Paytm, BHIM, CRED
                      </span>
                    </div>
                    <span style={{ fontSize: 18 }}>📱</span>
                  </label>

                  {/* Cards Option */}
                  <label
                    style={{
                      display: "flex",
                      alignItems: "center",
                      gap: 12,
                      padding: "12px 14px",
                      borderRadius: 10,
                      border: `1.5px solid ${mockMethod === "card" ? "#0c2340" : "#e2e8f0"}`,
                      background: mockMethod === "card" ? "#f8fafc" : "#fff",
                      cursor: "pointer",
                      transition: "all 0.15s ease",
                    }}
                  >
                    <input
                      type="radio"
                      name="mockMethod"
                      checked={mockMethod === "card"}
                      onChange={() => setMockMethod("card")}
                    />
                    <div style={{ flex: 1 }}>
                      <strong style={{ display: "block", fontSize: 13, color: "#1e293b" }}>
                        Cards (Credit / Debit)
                      </strong>
                      <span style={{ fontSize: 11, color: "#64748b" }}>
                        Visa, MasterCard, RuPay, Maestro
                      </span>
                    </div>
                    <span style={{ fontSize: 18 }}>💳</span>
                  </label>

                  {/* Netbanking Option */}
                  <label
                    style={{
                      display: "flex",
                      alignItems: "center",
                      gap: 12,
                      padding: "12px 14px",
                      borderRadius: 10,
                      border: `1.5px solid ${mockMethod === "netbanking" ? "#0c2340" : "#e2e8f0"}`,
                      background: mockMethod === "netbanking" ? "#f8fafc" : "#fff",
                      cursor: "pointer",
                      transition: "all 0.15s ease",
                    }}
                  >
                    <input
                      type="radio"
                      name="mockMethod"
                      checked={mockMethod === "netbanking"}
                      onChange={() => setMockMethod("netbanking")}
                    />
                    <div style={{ flex: 1 }}>
                      <strong style={{ display: "block", fontSize: 13, color: "#1e293b" }}>
                        Net Banking
                      </strong>
                      <span style={{ fontSize: 11, color: "#64748b" }}>
                        HDFC, SBI, ICICI, Axis, Kotak &amp; 50+ Banks
                      </span>
                    </div>
                    <span style={{ fontSize: 18 }}>🏦</span>
                  </label>
                </div>

                {/* Action Buttons */}
                <div style={{ display: "flex", gap: 10, marginTop: 18 }}>
                  <button
                    type="button"
                    onClick={handleApproveMockPayment}
                    disabled={mockApproving}
                    style={{
                      flex: 1,
                      padding: "13px 18px",
                      background: "#0c2340",
                      color: "#FFFFFF",
                      border: "none",
                      borderRadius: 10,
                      fontWeight: 700,
                      fontSize: 14,
                      cursor: "pointer",
                      boxShadow: "0 4px 12px rgba(12, 35, 64, 0.25)",
                    }}
                  >
                    {mockApproving
                      ? "Verifying Payment…"
                      : `Pay ${formatINR(mockRazorpayModal.amountPaise)}`}
                  </button>
                  <button
                    type="button"
                    onClick={() => setMockRazorpayModal(null)}
                    disabled={mockApproving}
                    style={{
                      padding: "13px 16px",
                      background: "#f1f5f9",
                      color: "#475569",
                      border: "none",
                      borderRadius: 10,
                      fontWeight: 600,
                      fontSize: 13,
                      cursor: "pointer",
                    }}
                  >
                    Cancel
                  </button>
                </div>

                <div
                  style={{
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                    gap: 6,
                    marginTop: 14,
                    fontSize: 11,
                    color: "#94a3b8",
                  }}
                >
                  <span>🔒 Secured by 256-bit SSL</span>
                  <span>&bull;</span>
                  <span>Razorpay Verified</span>
                </div>
              </div>
            </div>
          </div>
        )}
      </section>
    </>
  );
}
