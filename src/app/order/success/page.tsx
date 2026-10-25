import Link from "next/link";
import { and, eq } from "drizzle-orm";
import type { Metadata } from "next";
import { getDb } from "@/db/client";
import { orderItems, orders } from "@/db/schema";
import { formatINR } from "@/lib/money";
import { Icon } from "@/components/Icon";

export const metadata: Metadata = {
  title: "Order Confirmed — SVG Dry Fruits",
};

export const dynamic = "force-dynamic";

export default async function OrderSuccessPage({
  searchParams,
}: {
  searchParams: Promise<{ orderNumber?: string; token?: string }>;
}) {
  const { orderNumber, token } = await searchParams;

  if (!orderNumber || !token) {
    return (
      <section className="success-page">
        <div className="success-box">
          <Icon name="shield" size={48} className="success-icon-warn" />
          <h1>Order confirmation</h1>
          <p>Missing or invalid order details. If you recently placed an order, please check your confirmation link or tracking details.</p>
          <Link className="button button-primary" href="/">Return to home</Link>
        </div>
      </section>
    );
  }

  const db = await getDb();
  const [order] = await db
    .select()
    .from(orders)
    .where(and(eq(orders.orderNumber, orderNumber), eq(orders.trackingToken, token)))
    .limit(1);

  if (!order) {
    return (
      <section className="success-page">
        <div className="success-box">
          <Icon name="shield" size={48} className="success-icon-warn" />
          <h1>Order not found</h1>
          <p>We could not find an order matching the specified credentials. Please verify your order number and tracking link.</p>
          <Link className="button button-primary" href="/">Return to home</Link>
        </div>
      </section>
    );
  }

  const items = await db
    .select()
    .from(orderItems)
    .where(eq(orderItems.orderId, order.id));

  const addr = (order.shippingAddress || {}) as {
    street?: string;
    landmark?: string;
    city?: string;
    state?: string;
    pincode?: string;
  };

  const trackingUrl = `/track?order=${encodeURIComponent(order.orderNumber)}&token=${encodeURIComponent(order.trackingToken)}`;

  return (
    <section className="success-page">
      <div className="success-card">
        <div className="success-header">
          <div className="success-check-badge">
            <Icon name="shield" size={36} />
          </div>
          <p className="success-kicker">THANK YOU FOR YOUR ORDER</p>
          <h1>Payment <em>confirmed!</em></h1>
          <p className="success-subtitle">
            Your order <strong>{order.orderNumber}</strong> has been received and verified. We are preparing your fresh dry fruits for dispatch.
          </p>
        </div>

        {/* Tracking Save Box */}
        <div className="success-track-banner">
          <div className="track-banner-text">
            <strong>Save your tracking link</strong>
            <p>Because customer accounts are not required, please bookmark this tracking link to check your order status and courier tracking anytime.</p>
          </div>
          <Link className="button button-primary track-cta-btn" href={trackingUrl}>
            <Icon name="truck" size={18} /> Track your order now
          </Link>
        </div>

        <div className="success-details-grid">
          {/* Order items */}
          <div className="success-items-col">
            <h2>Order items</h2>
            <ul className="success-item-list">
              {items.map((it) => (
                <li key={it.id} className="success-item-row">
                  <div>
                    <strong>{it.productName}</strong>
                    <span>{it.variantLabel} × {it.quantity}</span>
                  </div>
                  <span className="success-item-price">
                    {formatINR(it.unitPricePaise * it.quantity)}
                  </span>
                </li>
              ))}
            </ul>

            <dl className="success-totals">
              <div>
                <dt>Subtotal</dt>
                <dd>{formatINR(order.subtotalPaise)}</dd>
              </div>
              {order.discountPaise > 0 && (
                <div className="discount-row">
                  <dt>Discount</dt>
                  <dd>−{formatINR(order.discountPaise)}</dd>
                </div>
              )}
              <div>
                <dt>Shipping</dt>
                <dd>{order.shippingPaise ? formatINR(order.shippingPaise) : "Free"}</dd>
              </div>
              <div className="success-grand-total">
                <dt>Total paid</dt>
                <dd>{formatINR(order.totalPaise)}</dd>
              </div>
            </dl>
          </div>

          {/* Delivery & Payment Info */}
          <div className="success-meta-col">
            <div className="success-meta-card">
              <h3>Delivery address</h3>
              <p className="success-addr-name"><strong>{order.customerName}</strong></p>
              <p>{addr.street}</p>
              {addr.landmark && <p>Near: {addr.landmark}</p>}
              <p>{addr.city}, {addr.state} — {addr.pincode}</p>
              <p className="success-meta-phone">Phone: +91 {order.customerPhone}</p>
              {order.customerEmail && <p>Email: {order.customerEmail}</p>}
            </div>

            <div className="success-meta-card">
              <h3>Payment summary</h3>
              <p>Status: <span className="badge badge-success">Paid</span></p>
              <p>Gateway: Razorpay Online</p>
              <p className="success-note">
                Our team will carefully pack your order in fresh packaging and dispatch it via our trusted courier partners.
              </p>
            </div>
          </div>
        </div>

        <div className="success-foot-actions">
          <Link className="button button-outline" href="/shop">
            ← Continue shopping
          </Link>
        </div>
      </div>
    </section>
  );
}
