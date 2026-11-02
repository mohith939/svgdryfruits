"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { clearCart, pruneMissing, useCart } from "@/lib/cart";
import { formatINR } from "@/lib/money";
import { Icon } from "@/components/Icon";
import { launchCashfreeCheckout } from "@/lib/cashfree-client";

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

      // Direct redirection to Cashfree hosted checkout page
      try {
        const mode = String(orderData.cfEnvironment || "").toLowerCase().includes("sandbox") || orderData.cfEnvironment === 1
          ? "sandbox"
          : "production";

        const result = await launchCashfreeCheckout({
          paymentSessionId: orderData.paymentSessionId,
          redirectTarget: "_self", // Directly navigates to Cashfree hosted checkout page
          mode,
        });

        if (result?.error) {
          setError(result.error.message || "Payment checkout could not be opened. Please try again.");
          setSubmitting(false);
          return;
        }
      } catch (cfErr: any) {
        console.error("Cashfree checkout error:", cfErr);
        setError(cfErr?.message || "Unable to redirect to Cashfree checkout. Please try again.");
        setSubmitting(false);
      }
    } catch (err: unknown) {
      console.error(err);
      setError("An unexpected error occurred. Please try again.");
      setSubmitting(false);
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
                  <strong>Cashfree Secure Online Payment</strong>
                </div>
                <p>Pay securely using UPI (Google Pay, PhonePe, Paytm), NetBanking, Credit or Debit Cards. Cash on delivery is not offered.</p>
              </div>

              <button
                type="submit"
                className="button button-primary checkout-submit-btn"
                disabled={submitting || unavailable}
              >
                {submitting ? "Redirecting to Cashfree…" : unavailable ? "Fix unavailable items" : `Pay ${formatINR(data.totals.totalPaise)} with Cashfree`}
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

      </section>
    </>
  );
}
