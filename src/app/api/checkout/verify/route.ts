import { NextResponse } from "next/server";
import { eq, sql } from "drizzle-orm";
import { z } from "zod";
import { getDb } from "@/db/client";
import { auditEvents, coupons, orders, payments } from "@/db/schema";
import { commitReservations } from "@/lib/inventory";
import { verifyPaymentSignature } from "@/lib/razorpay";

const verifySchema = z.object({
  orderId: z.string().uuid(),
  razorpayOrderId: z.string().min(1),
  razorpayPaymentId: z.string().min(1),
  razorpaySignature: z.string().min(1),
});

export async function POST(req: Request) {
  try {
    const raw = await req.json().catch(() => null);
    const parsed = verifySchema.safeParse(raw);

    if (!parsed.success) {
      return NextResponse.json({ error: "Invalid payment verification data" }, { status: 400 });
    }

    const { orderId, razorpayOrderId, razorpayPaymentId, razorpaySignature } = parsed.data;

    // Verify cryptographic HMAC signature
    const isValid = verifyPaymentSignature({
      razorpayOrderId,
      razorpayPaymentId,
      razorpaySignature,
    });

    if (!isValid) {
      return NextResponse.json({ error: "Payment verification failed. Invalid gateway signature." }, { status: 400 });
    }

    const db = await getDb();
    const [order] = await db.select().from(orders).where(eq(orders.id, orderId)).limit(1);

    if (!order) {
      return NextResponse.json({ error: "Order not found" }, { status: 404 });
    }

    // Idempotent: if already paid, return success without double processing
    if (order.paymentStatus === "paid") {
      return NextResponse.json({
        ok: true,
        alreadyProcessed: true,
        orderNumber: order.orderNumber,
        trackingToken: order.trackingToken,
      });
    }

    // Atomically commit inventory and transition order state
    await db.transaction(async (tx) => {
      // Record payment
      await tx.insert(payments).values({
        orderId: order.id,
        razorpayPaymentId,
        amountPaise: order.totalPaise,
        status: "captured",
      }).onConflictDoNothing();

      // Update order status
      await tx.update(orders).set({
        paymentStatus: "paid",
        fulfillmentStatus: "confirmed",
      }).where(eq(orders.id, order.id));

      // Commit inventory reservation permanently
      await commitReservations(tx as unknown as Parameters<typeof commitReservations>[0], order.id);

      // Increment coupon usage count if used
      if (order.couponCode) {
        await tx.update(coupons)
          .set({ timesUsed: sql`${coupons.timesUsed} + 1` })
          .where(eq(coupons.code, order.couponCode));
      }

      // Record audit event
      await tx.insert(auditEvents).values({
        actor: "customer_checkout",
        action: "payment_captured",
        detail: {
          orderNumber: order.orderNumber,
          razorpayPaymentId,
          amountPaise: order.totalPaise,
        },
      });
    });

    return NextResponse.json({
      ok: true,
      orderNumber: order.orderNumber,
      trackingToken: order.trackingToken,
    });
  } catch (err: unknown) {
    console.error("Payment verification error:", err);
    return NextResponse.json({ error: "Error verifying payment. Please contact support." }, { status: 500 });
  }
}
