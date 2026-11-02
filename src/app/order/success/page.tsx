import Link from "next/link";
import { and, eq, or } from "drizzle-orm";
import type { Metadata } from "next";
import { getDb } from "@/db/client";
import { auditEvents, orderItems, orders, payments } from "@/db/schema";
import { formatINR } from "@/lib/money";
import { Icon } from "@/components/Icon";
import { fetchCashfreeOrder } from "@/lib/cashfree";
import { commitReservations } from "@/lib/inventory";
import { OrderSuccessSync } from "@/components/OrderSuccessSync";

export const metadata: Metadata = {
  title: "Order Confirmed — SVG Dry Fruits",
};

export const dynamic = "force-dynamic";

export default async function OrderSuccessPage({
  searchParams,
}: {
  searchParams: Promise<{ orderNumber?: string; token?: string; order_id?: string }>;
}) {
  const { orderNumber, token, order_id } = await searchParams;
  const targetId = orderNumber || order_id;

  if (!targetId) {
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
  let [order] = await db
    .select()
    .from(orders)
    .where(
      token
        ? and(
            or(eq(orders.orderNumber, targetId), eq(orders.cashfreeOrderId, targetId)),
            eq(orders.trackingToken, token)
          )
        : or(eq(orders.orderNumber, targetId), eq(orders.cashfreeOrderId, targetId))
    )
    .limit(1);

  if (!order) {
    return (
      <section className="success-page">
        <div className="success-box">
          <Icon name="shield" size={48} className="success-icon-warn" />
          <h1>Order not found</h1>
          <p>We could not find an order matching {targetId}. If you just completed payment, please wait a moment or contact our support team.</p>
          <Link className="button button-primary" href="/">Return to home</Link>
        </div>
      </section>
    );
  }

  // If order is still awaiting payment, verify directly with Cashfree
  if (order.paymentStatus !== "paid" && (order.cashfreeOrderId || order.orderNumber)) {
    try {
      const cfStatus = await fetchCashfreeOrder(order.cashfreeOrderId || order.orderNumber);
      if (cfStatus.order_status === "PAID") {
        await db.transaction(async (tx) => {
          // Record payment
          await tx.insert(payments).values({
            orderId: order.id,
            cashfreePaymentId: `cf_pay_success_${Date.now()}`,
            amountPaise: order.totalPaise,
            status: "captured",
          }).onConflictDoNothing();

          // Update order status
          await tx.update(orders).set({
            paymentStatus: "paid",
            fulfillmentStatus: "confirmed",
          }).where(eq(orders.id, order.id));

          // Commit inventory reservation
          await commitReservations(tx as unknown as Parameters<typeof commitReservations>[0], order.id);

          // Record audit event
          await tx.insert(auditEvents).values({
            actor: "cashfree_return_redirect",
            action: "payment_captured",
            detail: {
              orderNumber: order.orderNumber,
              cfStatus: cfStatus.order_status,
            },
          });
        });

        // Refresh updated order instance
        const [refreshed] = await db.select().from(orders).where(eq(orders.id, order.id)).limit(1);
        if (refreshed) {
          order = refreshed;
        }
      }
    } catch (err) {
      console.error("Error fetching order status from Cashfree on success page:", err);
    }
  }

  const trackingUrl = `/track?order=${encodeURIComponent(order.orderNumber)}&token=${encodeURIComponent(order.trackingToken)}`;

  // If payment is not yet confirmed
  if (order.paymentStatus !== "paid") {
    return (
      <section className="success-page">
        <div className="success-box">
          <Icon name="shield" size={48} className="success-icon-warn" />
          <h1>Payment pending confirmation</h1>
          <p>
            Your order <strong>{order.orderNumber}</strong> was placed, but Cashfree has not yet marked the transaction as complete.
          </p>
          <p className="success-note" style={{ marginTop: 12, color: "#64748b" }}>
            If your account was debited, payment typically confirms within 1–2 minutes. You can refresh this page or track the order anytime.
          </p>
          <div style={{ display: "flex", gap: 12, justifyContent: "center", marginTop: 24 }}>
            <Link className="button button-primary" href={trackingUrl}>
              <Icon name="truck" size={18} /> Track your order
            </Link>
            <Link className="button button-outline" href="/checkout">
              Return to Checkout
            </Link>
          </div>
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

  return (
    <section className="success-page">
      <OrderSuccessSync />
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
              <p>Gateway: Cashfree Online</p>
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
