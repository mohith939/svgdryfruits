import { NextResponse } from "next/server";
import { eq, sql } from "drizzle-orm";
import { getDb } from "@/db/client";
import { auditEvents, coupons, orders, payments, webhookEvents } from "@/db/schema";
import { commitReservations, releaseReservations } from "@/lib/inventory";
import { verifyWebhookSignature } from "@/lib/razorpay";

export async function POST(req: Request) {
  try {
    const rawBody = await req.text();
    const signature = req.headers.get("x-razorpay-signature");

    if (process.env.RAZORPAY_WEBHOOK_SECRET) {
      const isValid = verifyWebhookSignature(rawBody, signature);
      if (!isValid) {
        return NextResponse.json({ error: "Invalid webhook signature" }, { status: 400 });
      }
    }

    const payload = JSON.parse(rawBody);
    const event = payload.event;
    const eventId = payload.event_id || `${event}_${Date.now()}_${Math.random()}`;

    const db = await getDb();

    // Idempotency: skip if event has already been processed
    const [existingEvent] = await db.select().from(webhookEvents).where(eq(webhookEvents.eventId, eventId)).limit(1);
    if (existingEvent) {
      return NextResponse.json({ status: "duplicate_skipped" });
    }

    await db.insert(webhookEvents).values({
      eventId,
      type: event,
    }).onConflictDoNothing();

    if (event === "order.paid" || event === "payment.captured") {
      const paymentEntity = payload.payload?.payment?.entity;
      const orderEntity = payload.payload?.order?.entity;
      const razorpayOrderId = paymentEntity?.order_id || orderEntity?.id;

      if (razorpayOrderId) {
        const [order] = await db.select().from(orders).where(eq(orders.razorpayOrderId, razorpayOrderId)).limit(1);
        if (order && order.paymentStatus !== "paid") {
          await db.transaction(async (tx) => {
            if (paymentEntity?.id) {
              await tx.insert(payments).values({
                orderId: order.id,
                razorpayPaymentId: paymentEntity.id,
                amountPaise: paymentEntity.amount ?? order.totalPaise,
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
              actor: "razorpay_webhook",
              action: event,
              detail: { orderNumber: order.orderNumber, eventId },
            });
          });
        }
      }
    } else if (event === "payment.failed") {
      const paymentEntity = payload.payload?.payment?.entity;
      const razorpayOrderId = paymentEntity?.order_id;
      if (razorpayOrderId) {
        const [order] = await db.select().from(orders).where(eq(orders.razorpayOrderId, razorpayOrderId)).limit(1);
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
    console.error("Razorpay webhook error:", err);
    return NextResponse.json({ error: "Webhook handling error" }, { status: 500 });
  }
}
