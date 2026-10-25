"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { useEffect, useState } from "react";
import { formatINR } from "@/lib/money";

type OrderItem = {
  id: string;
  productName: string;
  variantLabel: string;
  unitPricePaise: number;
  quantity: number;
};

type Payment = {
  id: string;
  razorpayPaymentId: string | null;
  amountPaise: number;
  status: string;
  createdAt: string;
};

type Refund = {
  id: string;
  amountPaise: number;
  note: string | null;
  createdAt: string;
};

type OrderData = {
  id: string;
  orderNumber: string;
  trackingToken: string;
  paymentStatus: string;
  fulfillmentStatus: string;
  customerName: string;
  customerEmail: string | null;
  customerPhone: string;
  shippingAddress: {
    street?: string;
    landmark?: string;
    city?: string;
    state?: string;
    pincode?: string;
  };
  subtotalPaise: number;
  discountPaise: number;
  shippingPaise: number;
  totalPaise: number;
  couponCode: string | null;
  razorpayOrderId: string | null;
  courier: string | null;
  trackingNumber: string | null;
  createdAt: string;
};

export default function AdminOrderDetailPage() {
  const params = useParams();
  const id = params?.id as string;

  const [order, setOrder] = useState<OrderData | null>(null);
  const [items, setItems] = useState<OrderItem[]>([]);
  const [payments, setPayments] = useState<Payment[]>([]);
  const [refunds, setRefunds] = useState<Refund[]>([]);
  const [loading, setLoading] = useState(true);

  // Status & Dispatch form state
  const [fulfillmentStatus, setFulfillmentStatus] = useState("");
  const [courier, setCourier] = useState("Shadowfax Express");
  const [trackingNumber, setTrackingNumber] = useState("");
  const [statusMsg, setStatusMsg] = useState<string | null>(null);
  const [dispatching, setDispatching] = useState(false);

  // Refund form state
  const [refundINR, setRefundINR] = useState("");
  const [refundNote, setRefundNote] = useState("");
  const [restock, setRestock] = useState(true);
  const [markRefunded, setMarkRefunded] = useState(true);
  const [refundMsg, setRefundMsg] = useState<string | null>(null);
  const [refundLoading, setRefundLoading] = useState(false);

  useEffect(() => {
    if (!id) return;
    loadOrder();
  }, [id]);

  function loadOrder() {
    setLoading(true);
    fetch(`/api/admin/orders/${id}`)
      .then((r) => r.json())
      .then((d) => {
        if (d.order) {
          setOrder(d.order);
          setItems(d.items || []);
          setPayments(d.payments || []);
          setRefunds(d.refunds || []);
          setFulfillmentStatus(d.order.fulfillmentStatus);
          setCourier(d.order.courier || "Shadowfax Express");
          setTrackingNumber(d.order.trackingNumber || "");
        }
        setLoading(false);
      })
      .catch(() => setLoading(false));
  }

  async function handleDispatchViaShadowfax() {
    setDispatching(true);
    setStatusMsg(null);

    try {
      const res = await fetch(`/api/admin/orders/${id}/dispatch`, {
        method: "POST",
      });
      const data = await res.json();
      if (res.ok) {
        setStatusMsg(`✓ Dispatched via Shadowfax! AWB: ${data.dispatch.awbNumber}`);
        loadOrder();
      } else {
        setStatusMsg(data.error || "Failed to dispatch via Shadowfax");
      }
    } catch {
      setStatusMsg("Error generating Shadowfax dispatch");
    } finally {
      setDispatching(false);
    }
  }

  async function handleUpdateStatus(e: React.FormEvent) {
    e.preventDefault();
    setStatusMsg(null);
    try {
      const res = await fetch(`/api/admin/orders/${id}`, {
        method: "PUT",
        headers: { "content-type": "application/json" },
        body: JSON.stringify({
          fulfillmentStatus,
          courier: courier.trim() || null,
          trackingNumber: trackingNumber.trim() || null,
        }),
      });

      if (res.ok) {
        setStatusMsg("Order fulfillment status & tracking updated successfully.");
        loadOrder();
      } else {
        const err = await res.json();
        setStatusMsg(err.error || "Failed to update status");
      }
    } catch {
      setStatusMsg("Error updating order status.");
    }
  }

  async function handleRecordRefund(e: React.FormEvent) {
    e.preventDefault();
    if (!refundINR || Number(refundINR) <= 0) return;
    setRefundMsg(null);
    setRefundLoading(true);

    try {
      const res = await fetch(`/api/admin/orders/${id}/refund`, {
        method: "POST",
        headers: { "content-type": "application/json" },
        body: JSON.stringify({
          amountPaise: Math.round(Number(refundINR) * 100),
          note: refundNote.trim(),
          restock,
          markRefunded,
        }),
      });

      const data = await res.json();
      if (res.ok) {
        setRefundMsg("Manual refund recorded and saved.");
        setRefundINR("");
        setRefundNote("");
        loadOrder();
      } else {
        setRefundMsg(data.error || "Failed to record refund");
      }
    } catch {
      setRefundMsg("Error recording refund");
    } finally {
      setRefundLoading(false);
    }
  }

  if (loading) return <div className="admin-content"><p>Loading order details…</p></div>;
  if (!order) return <div className="admin-content"><p>Order not found.</p></div>;

  const addr = order.shippingAddress || {};
  const isShadowfax = order.courier?.toLowerCase().includes("shadowfax");

  return (
    <div className="admin-content">
      <div className="admin-page-header">
        <div>
          <div className="breadcrumbs">
            <Link href="/admin">Dashboard</Link>
            <span aria-hidden="true">/</span>
            <Link href="/admin/orders">Orders</Link>
            <span aria-hidden="true">/</span>
            <span>{order.orderNumber}</span>
          </div>
          <h1>Order <em>{order.orderNumber}</em></h1>
          <p>Placed on {new Date(order.createdAt).toLocaleString("en-IN")}</p>
        </div>
        <div style={{ display: "flex", gap: 10 }}>
          <Link
            href={`/track?order=${encodeURIComponent(order.orderNumber)}&token=${encodeURIComponent(order.trackingToken)}`}
            target="_blank"
            className="button button-outline"
            style={{ background: "#fff", color: "#073D26", borderColor: "#c4baa8" }}
          >
            Customer Tracking View <span aria-hidden="true">↗</span>
          </Link>
        </div>
      </div>

      <div className="admin-two-cols">
        {/* Left Column: Items and Customer Info */}
        <div className="admin-col">
          {/* Items snapshot */}
          <div className="admin-card">
            <h2>Ordered Items (Snapshot)</h2>
            <div className="admin-table-wrap">
              <table className="admin-table">
                <thead>
                  <tr>
                    <th>Item</th>
                    <th>Pack Size</th>
                    <th>Unit Price</th>
                    <th>Qty</th>
                    <th>Total</th>
                  </tr>
                </thead>
                <tbody>
                  {items.map((it) => (
                    <tr key={it.id}>
                      <td><strong>{it.productName}</strong></td>
                      <td>{it.variantLabel}</td>
                      <td>{formatINR(it.unitPricePaise)}</td>
                      <td>{it.quantity}</td>
                      <td><strong>{formatINR(it.unitPricePaise * it.quantity)}</strong></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <dl className="admin-order-totals">
              <div>
                <dt>Subtotal</dt>
                <dd>{formatINR(order.subtotalPaise)}</dd>
              </div>
              {order.discountPaise > 0 && (
                <div className="discount-row">
                  <dt>Discount ({order.couponCode || "Coupon"})</dt>
                  <dd>−{formatINR(order.discountPaise)}</dd>
                </div>
              )}
              <div>
                <dt>Shipping</dt>
                <dd>{order.shippingPaise ? formatINR(order.shippingPaise) : "Free"}</dd>
              </div>
              <div className="admin-grand-total">
                <dt>Final Total</dt>
                <dd>{formatINR(order.totalPaise)}</dd>
              </div>
            </dl>
          </div>

          {/* Customer & Delivery address */}
          <div className="admin-card">
            <h2>Customer &amp; Shipping Details</h2>
            <div className="admin-details-grid">
              <div>
                <span className="admin-detail-label">Customer Name</span>
                <strong>{order.customerName}</strong>
              </div>
              <div>
                <span className="admin-detail-label">Mobile Number</span>
                <strong>+91 {order.customerPhone}</strong>
              </div>
              <div>
                <span className="admin-detail-label">Email Address</span>
                <span>{order.customerEmail || "Not provided"}</span>
              </div>
              <div>
                <span className="admin-detail-label">Delivery Address</span>
                <p className="admin-addr-p">
                  {addr.street}<br />
                  {addr.landmark && <>Landmark: {addr.landmark}<br /></>}
                  {addr.city}, {addr.state} — <strong>{addr.pincode}</strong>
                </p>
              </div>
            </div>
          </div>

          {/* Payment History */}
          <div className="admin-card">
            <h2>Payment &amp; Gateway Information</h2>
            <div className="admin-details-grid">
              <div>
                <span className="admin-detail-label">Payment Status</span>
                <span className={`badge ${order.paymentStatus === "paid" ? "badge-success" : order.paymentStatus === "pending" ? "badge-warning" : "badge-danger"}`}>
                  {order.paymentStatus}
                </span>
              </div>
              <div>
                <span className="admin-detail-label">Razorpay Order ID</span>
                <code>{order.razorpayOrderId || "—"}</code>
              </div>
            </div>

            {payments.length > 0 && (
              <div className="admin-subtable">
                <h3>Gateway Payments</h3>
                <ul>
                  {payments.map((p) => (
                    <li key={p.id}>
                      <span>ID: <code>{p.razorpayPaymentId}</code> ({p.status})</span>
                      <strong>{formatINR(p.amountPaise)}</strong>
                    </li>
                  ))}
                </ul>
              </div>
            )}
          </div>
        </div>

        {/* Right Column: Fulfillment, Shadowfax Dispatch, and Manual Refund */}
        <div className="admin-col">
          {/* Quick Shadowfax Dispatch Box */}
          <div
            className="admin-card"
            style={{
              background: "#FAF7F2",
              border: "1px solid #D6C7B2",
            }}
          >
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-start", gap: 10 }}>
              <div>
                <h2 style={{ margin: "0 0 4px", fontSize: 16, color: "#073D26" }}>
                  🚚 Shadowfax Logistics Partner
                </h2>
                <p style={{ margin: 0, fontSize: 12.5, color: "#555" }}>
                  Generate express waybill and arrange door-step delivery pickup.
                </p>
              </div>
              <button
                type="button"
                onClick={handleDispatchViaShadowfax}
                disabled={dispatching || order.fulfillmentStatus === "shipped" || order.fulfillmentStatus === "delivered"}
                className="button button-primary"
                style={{
                  fontSize: 12.5,
                  padding: "8px 14px",
                  whiteSpace: "nowrap",
                  background: "#073D26",
                  color: "#E2C376",
                }}
              >
                {dispatching
                  ? "Dispatching…"
                  : order.fulfillmentStatus === "shipped" || order.fulfillmentStatus === "delivered"
                  ? "✓ Dispatched"
                  : "⚡ 1-Click Shadowfax Dispatch"}
              </button>
            </div>

            {order.trackingNumber && isShadowfax && (
              <div
                style={{
                  marginTop: 12,
                  padding: "8px 12px",
                  background: "#fff",
                  borderRadius: 6,
                  border: "1px solid #E0D6C5",
                  fontSize: 12.5,
                  display: "flex",
                  justifyContent: "space-between",
                  alignItems: "center",
                }}
              >
                <span>
                  Shadowfax AWB: <strong>{order.trackingNumber}</strong>
                </span>
                <a
                  href={`https://tracker.shadowfax.in/#/track?awb=${order.trackingNumber}`}
                  target="_blank"
                  rel="noreferrer"
                  style={{ color: "#073D26", fontWeight: 600, textDecoration: "underline" }}
                >
                  Track on Shadowfax ↗
                </a>
              </div>
            )}
          </div>

          {/* Fulfillment update form */}
          <div className="admin-card">
            <h2>Manual Fulfillment &amp; Dispatch</h2>
            {statusMsg && <div className="admin-info-banner">{statusMsg}</div>}

            <form className="admin-form" onSubmit={handleUpdateStatus}>
              <div className="form-group">
                <label htmlFor="fstatus">Fulfillment Status</label>
                <select id="fstatus" value={fulfillmentStatus} onChange={(e) => setFulfillmentStatus(e.target.value)}>
                  <option value="awaiting_payment">Awaiting Payment</option>
                  <option value="confirmed">Confirmed (Payment Verified)</option>
                  <option value="processing">Processing (Selecting Stock)</option>
                  <option value="packed">Packed &amp; Sealed</option>
                  <option value="shipped">Shipped (Dispatched)</option>
                  <option value="delivered">Delivered to Customer</option>
                  <option value="cancelled">Cancelled</option>
                </select>
              </div>

              <div className="form-group">
                <label htmlFor="courier">Courier / Delivery Partner</label>
                <select
                  id="courier"
                  value={courier}
                  onChange={(e) => setCourier(e.target.value)}
                  style={{ marginBottom: 6 }}
                >
                  <option value="Shadowfax Express">Shadowfax Express (Recommended)</option>
                  <option value="Blue Dart">Blue Dart</option>
                  <option value="Delhivery">Delhivery</option>
                  <option value="DTDC">DTDC</option>
                  <option value="India Post">India Post Speed Post</option>
                  <option value="Custom">Other Courier...</option>
                </select>
                {courier === "Custom" && (
                  <input
                    placeholder="Enter courier name"
                    onChange={(e) => setCourier(e.target.value)}
                  />
                )}
              </div>

              <div className="form-group">
                <label htmlFor="tracknum">Courier Tracking / AWB Number</label>
                <input
                  id="tracknum"
                  value={trackingNumber}
                  onChange={(e) => setTrackingNumber(e.target.value)}
                  placeholder="e.g. SFX2026123456 or AWB987654321"
                />
              </div>

              <button
                type="submit"
                className="button button-primary"
                style={{ background: "#073D26", color: "#fff" }}
              >
                Save Tracking &amp; Status
              </button>
            </form>
          </div>

          {/* Section 21: Manual Refund Recording */}
          <div className="admin-card">
            <h2>Record Manual Refund</h2>
            <p className="admin-card-subtitle">
              Refunds are initiated manually via the Razorpay dashboard. Record the verification details below to keep store records accurate.
            </p>
            {refundMsg && <div className="admin-info-banner">{refundMsg}</div>}

            <form className="admin-form" onSubmit={handleRecordRefund}>
              <div className="form-group">
                <label htmlFor="refamt">Refund Amount (₹) *</label>
                <input
                  id="refamt"
                  type="number"
                  required
                  min="1"
                  step="0.01"
                  value={refundINR}
                  onChange={(e) => setRefundINR(e.target.value)}
                  placeholder={`Max ${order.totalPaise / 100}`}
                />
              </div>

              <div className="form-group">
                <label htmlFor="refnote">Reason / Razorpay Refund Reference *</label>
                <input
                  id="refnote"
                  required
                  value={refundNote}
                  onChange={(e) => setRefundNote(e.target.value)}
                  placeholder="e.g. Customer requested cancellation / rzp_rfnd_123"
                />
              </div>

              <div className="form-checkbox-row">
                <label className="checkbox-label">
                  <input type="checkbox" checked={restock} onChange={(e) => setRestock(e.target.checked)} />
                  <span>Restock ordered items back to inventory</span>
                </label>
                <label className="checkbox-label">
                  <input type="checkbox" checked={markRefunded} onChange={(e) => setMarkRefunded(e.target.checked)} />
                  <span>Mark order payment status as refunded</span>
                </label>
              </div>

              <button
                type="submit"
                className="button button-outline"
                disabled={refundLoading}
                style={{ background: "#fff", color: "#073D26", borderColor: "#c4baa8" }}
              >
                {refundLoading ? "Recording…" : "Record Manual Refund"}
              </button>
            </form>

            {refunds.length > 0 && (
              <div className="admin-subtable">
                <h3>Recorded Refund History</h3>
                <ul>
                  {refunds.map((r) => (
                    <li key={r.id}>
                      <div>
                        <strong>{formatINR(r.amountPaise)}</strong>
                        <span className="admin-cell-sub">{r.note}</span>
                      </div>
                      <span className="admin-muted">
                        {new Date(r.createdAt).toLocaleDateString("en-IN")}
                      </span>
                    </li>
                  ))}
                </ul>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
