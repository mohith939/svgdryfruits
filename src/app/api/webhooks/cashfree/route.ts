import { NextResponse } from "next/server";
import { eq, sql } from "drizzle-orm";
import { getDb } from "@/db/client";
import { auditEvents, coupons, orders, payments, webhookEvents } from "@/db/schema";
import { commitReservations, releaseReservations } from "@/lib/inventory";
import { isCashfreeConfigured, verifyCashfreeWebhook } from "@/lib/cashfree";

export async function POST(req: Request) {
  try {
    const rawBody = await req.text();
    const signature = req.headers.get("x-webhook-signature");
    const timestamp = req.headers.get("x-webhook-timestamp");

    if (isCashfreeConfigured() && signature && timestamp) {
      try {
        verifyCashfreeWebhook(signature, rawBody, timestamp);
      } catch (sigErr) {
        console.error("Cashfree webhook signature verification failed:", sigErr);
        return NextResponse.json({ error: "Invalid webhook signature" }, { status: 400 });
      }
    }

    const payload = JSON.parse(rawBody);
    const eventType = payload.type || payload.event || "UNKNOWN";
    const orderData = payload.data?.order || payload.order || {};
    const paymentData = payload.data?.payment || payload.payment || {};
    const cashfreeOrderId = orderData.order_id || payload.order_id;
    const cashfreePaymentId = paymentData.cf_payment_id ? String(paymentData.cf_payment_id) : paymentData.payment_id;

    const eventId =
      req.headers.get("x-webhook-id") ||
      payload.event_id ||
      `${eventType}_${cashfreeOrderId || "no_order"}_${cashfreePaymentId || Date.now()}`;

    const db = await getDb();

    // Idempotency: skip duplicate webhook events
    const [existingEvent] = await db.select().from(webhookEvents).where(eq(webhookEvents.eventId, eventId)).limit(1);
    if (existingEvent) {
      return NextResponse.json({ status: "duplicate_skipped" });
    }

    await db.insert(webhookEvents).values({
      eventId,
      type: eventType,
    }).onConflictDoNothing();

    if (eventType === "PAYMENT_SUCCESS_WEBHOOK" || eventType === "ORDER_PAID" || eventType === "order.paid") {
      if (cashfreeOrderId) {
        const [order] = await db.select().from(orders).where(eq(orders.cashfreeOrderId, cashfreeOrderId)).limit(1);
        if (order && order.paymentStatus !== "paid") {
          await db.transaction(async (tx) => {
            if (cashfreePaymentId) {
              const amountPaise = paymentData.payment_amount
                ? Math.round(Number(paymentData.payment_amount) * 100)
                : order.totalPaise;

              await tx.insert(payments).values({
                orderId: order.id,
                cashfreePaymentId,
                amountPaise,
                status: "captured",
              }).onConflictDoNothing();
            }

            await tx.update(orders).set({
              paymentStatus: "paid",
              fulfillmentStatus: "confirmed",
            }).where(eq(orders.id, order.id));

            await commitReservations(tx as unknown as Parameters<typeof commitReservations>[0], order.id);

            if (order.couponCode) {
              await tx.update(coupons)
                .set({ timesUsed: sql`${coupons.timesUsed} + 1` })
                .where(eq(coupons.code, order.couponCode));
            }

            await tx.insert(auditEvents).values({
              actor: "cashfree_webhook",
              action: eventType,
              detail: { orderNumber: order.orderNumber, eventId, cashfreePaymentId },
            });
          });
        }
      }
    } else if (eventType === "PAYMENT_FAILED_WEBHOOK" || eventType === "payment.failed") {
      if (cashfreeOrderId) {
        const [order] = await db.select().from(orders).where(eq(orders.cashfreeOrderId, cashfreeOrderId)).limit(1);
        if (order && order.paymentStatus === "pending") {
          await db.update(orders).set({
            paymentStatus: "failed",
          }).where(eq(orders.id, order.id));
          await releaseReservations(db, order.id);
        }
      }
    }

    return NextResponse.json({ status: "ok" });
  } catch (err: unknown) {
    console.error("Cashfree webhook error:", err);
    return NextResponse.json({ error: "Webhook handling error" }, { status: 500 });
  }
}
