"use client";

import Link from "next/link";
import { useEffect, useState, Suspense } from "react";
import { useSearchParams } from "next/navigation";
import { Icon } from "@/components/Icon";

type TrackResult = {
  orderNumber: string;
  createdAt: string;
  paymentStatus: string;
  fulfillmentStatus: string;
  courier: string | null;
  trackingNumber: string | null;
  destination: string | null;
  items: {
    productName: string;
    variantLabel: string;
    quantity: number;
  }[];
};

const STAGES = [
  { key: "confirmed", label: "Confirmed", desc: "Payment verified, order received" },
  { key: "processing", label: "Processing", desc: "Items being selected from inventory" },
  { key: "packed", label: "Packed", desc: "Freshly packed and sealed" },
  { key: "shipped", label: "Dispatched", desc: "Handed over to delivery partner" },
  { key: "delivered", label: "Delivered", desc: "Delivered to your doorstep" },
];

function stageIndex(status: string): number {
  switch (status.toLowerCase()) {
    case "awaiting_payment":
    case "pending":
      return 0;
    case "confirmed":
      return 1;
    case "processing":
      return 2;
    case "packed":
      return 3;
    case "shipped":
      return 4;
    case "delivered":
      return 5;
    case "cancelled":
      return -1;
    default:
      return 1;
  }
}

function TrackContent() {
  const searchParams = useSearchParams();
  const [activeTab, setActiveTab] = useState<"order" | "phone">("order");
  const [orderNumber, setOrderNumber] = useState(searchParams.get("order") ?? "");
  const [token, setToken] = useState(searchParams.get("token") ?? "");
  const [phone, setPhone] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [result, setResult] = useState<TrackResult | null>(null);

  useEffect(() => {
    const o = searchParams.get("order");
    const t = searchParams.get("token");
    if (o) {
      setOrderNumber(o);
      if (t) setToken(t);
      fetchTracking(o, t ?? "", "");
    }
  }, [searchParams]);

  async function fetchTracking(num: string, tok: string, ph: string) {
    if (activeTab === "order" && !num.trim()) {
      setError("Please enter your Order Number (e.g. SVG-20261025-6411).");
      return;
    }
    if (activeTab === "phone" && !ph.trim()) {
      setError("Please enter your 10-digit mobile number.");
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const res = await fetch("/api/orders/track", {
        method: "POST",
        headers: { "content-type": "application/json" },
        body: JSON.stringify({
          orderNumber: activeTab === "order" ? num.trim() : undefined,
          token: tok.trim() || undefined,
          phone: activeTab === "phone" ? ph.trim() : undefined,
        }),
      });

      const data = await res.json();
      if (!res.ok) {
        setError(data.error || "Unable to find order details. Please double-check your input.");
        setResult(null);
      } else {
        setResult(data);
      }
    } catch {
      setError("Network error while searching for order. Please try again.");
    } finally {
      setLoading(false);
    }
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    fetchTracking(orderNumber, token, phone);
  }

  const currentStage = result ? stageIndex(result.fulfillmentStatus) : 0;
  const isCancelled = result?.fulfillmentStatus === "cancelled";

  return (
    <section className="track-page">
      <div className="track-head">
        <div className="breadcrumbs">
          <Link href="/">Home</Link>
          <span aria-hidden="true">/</span>
          <span>Track Order</span>
        </div>
        <h1>Track your <em>order.</em></h1>
        <p className="track-sub">Follow your fresh dry fruits from our packaging facility to your doorstep.</p>
      </div>

      <div className="track-container">
        {/* Tracking tabs */}
        <div style={{ display: "flex", gap: 10, marginBottom: 20 }}>
          <button
            type="button"
            onClick={() => {
              setActiveTab("order");
              setError(null);
            }}
            style={{
              flex: 1,
              padding: "10px 14px",
              borderRadius: 8,
              border: "1.5px solid",
              borderColor: activeTab === "order" ? "#073D26" : "#D6C8B5",
              background: activeTab === "order" ? "#073D26" : "#FFFFFF",
              color: activeTab === "order" ? "#FFFFFF" : "#4A4031",
              fontWeight: 600,
              fontSize: 14,
              cursor: "pointer",
            }}
          >
            Track with Order Number
          </button>
          <button
            type="button"
            onClick={() => {
              setActiveTab("phone");
              setError(null);
            }}
            style={{
              flex: 1,
              padding: "10px 14px",
              borderRadius: 8,
              border: "1.5px solid",
              borderColor: activeTab === "phone" ? "#073D26" : "#D6C8B5",
              background: activeTab === "phone" ? "#073D26" : "#FFFFFF",
              color: activeTab === "phone" ? "#FFFFFF" : "#4A4031",
              fontWeight: 600,
              fontSize: 14,
              cursor: "pointer",
            }}
          >
            Track with Mobile Number
          </button>
        </div>

        {/* Tracking form */}
        <form className="track-form" onSubmit={handleSubmit}>
          {activeTab === "order" ? (
            <div className="form-group">
              <label htmlFor="orderNumber">Order Number *</label>
              <input
                id="orderNumber"
                value={orderNumber}
                onChange={(e) => setOrderNumber(e.target.value)}
                placeholder="e.g. SVG-20261025-6411 or 20261025-6411"
                required
              />
              <span style={{ fontSize: 12, color: "#776F62", marginTop: 4 }}>
                Enter the order number displayed on your order confirmation.
              </span>
            </div>
          ) : (
            <div className="form-group">
              <label htmlFor="phone">Mobile Number *</label>
              <input
                id="phone"
                type="tel"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                placeholder="10-digit mobile number (e.g. 9876543210)"
                required
              />
              <span style={{ fontSize: 12, color: "#776F62", marginTop: 4 }}>
                Enter the phone number used during checkout to find your latest order.
              </span>
            </div>
          )}

          <button className="button button-primary track-btn" type="submit" disabled={loading} style={{ marginTop: 12 }}>
            {loading ? "Locating order…" : "Track Order Status"}
          </button>
        </form>

        {error && (
          <div className="track-error-box" role="alert">
            <Icon name="shield" size={20} />
            <p>{error}</p>
          </div>
        )}

        {/* Results view */}
        {result && (
          <div className="track-result-card">
            <div className="track-status-header">
              <div>
                <span className="track-kicker">ORDER #{result.orderNumber}</span>
                <h2>Status: <em>{result.fulfillmentStatus.replace("_", " ")}</em></h2>
                {result.destination && <p className="track-dest">Delivering to: {result.destination}</p>}
              </div>
              <div className="track-date">
                Placed on {new Date(result.createdAt).toLocaleDateString("en-IN", { day: "numeric", month: "short", year: "numeric" })}
              </div>
            </div>

            {/* Courier dispatch card */}
            {result.courier && (
              <div className="track-courier-box">
                <Icon name="truck" size={24} />
                <div>
                  <strong>Courier: {result.courier}</strong>
                  {result.trackingNumber && <p>Waybill / Tracking No: <code>{result.trackingNumber}</code></p>}
                </div>
              </div>
            )}

            {/* Visual Timeline */}
            {isCancelled ? (
              <div className="track-cancelled-notice">
                <Icon name="shield" size={24} />
                <p>This order has been cancelled. For refunds or assistance, please reach out via our Contact page or WhatsApp.</p>
              </div>
            ) : (
              <div className="track-timeline">
                {STAGES.map((s, idx) => {
                  const isDone = currentStage >= idx + 1;
                  const isCurrent = currentStage === idx + 1;
                  return (
                    <div key={s.key} className={`timeline-step ${isDone ? "is-done" : ""} ${isCurrent ? "is-current" : ""}`}>
                      <div className="step-indicator">
                        <span className="step-dot" />
                        {idx < STAGES.length - 1 && <span className="step-line" />}
                      </div>
                      <div className="step-details">
                        <strong>{s.label}</strong>
                        <p>{s.desc}</p>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}

            {/* Items in order */}
            <div className="track-items-box">
              <h3>Items in this shipment</h3>
              <ul>
                {result.items.map((it, idx) => (
                  <li key={idx}>
                    <span>{it.productName} ({it.variantLabel})</span>
                    <strong>Qty: {it.quantity}</strong>
                  </li>
                ))}
              </ul>
            </div>
          </div>
        )}
      </div>
    </section>
  );
}

export default function TrackPage() {
  return (
    <Suspense fallback={<div className="track-page"><p>Loading tracking…</p></div>}>
      <TrackContent />
    </Suspense>
  );
}
