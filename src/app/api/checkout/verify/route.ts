import { NextResponse } from "next/server";
import { eq, or, sql } from "drizzle-orm";
import { z } from "zod";
import { getDb } from "@/db/client";
import { auditEvents, coupons, orders, payments } from "@/db/schema";
import { commitReservations } from "@/lib/inventory";
import { fetchCashfreeOrder, isCashfreeConfigured } from "@/lib/cashfree";

const verifySchema = z.object({
  orderId: z.string().optional(),
  orderNumber: z.string().optional(),
  cashfreeOrderId: z.string().optional(),
  cashfreePaymentId: z.string().optional(),
  isMock: z.boolean().optional(),
}).refine((data) => data.orderId || data.orderNumber || data.cashfreeOrderId, {
  message: "orderId, orderNumber, or cashfreeOrderId is required",
});

export async function POST(req: Request) {
  try {
    const raw = await req.json().catch(() => null);
    const parsed = verifySchema.safeParse(raw);

    if (!parsed.success) {
      return NextResponse.json({ error: "Invalid payment verification data" }, { status: 400 });
    }

    const { orderId, orderNumber, cashfreeOrderId, cashfreePaymentId, isMock } = parsed.data;

    const db = await getDb();
    let order;
    if (orderId && /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(orderId)) {
      [order] = await db.select().from(orders).where(eq(orders.id, orderId)).limit(1);
    } else {
      const identifier = orderNumber || cashfreeOrderId || orderId;
      if (identifier) {
        [order] = await db.select().from(orders).where(
          or(
            eq(orders.orderNumber, identifier),
            eq(orders.cashfreeOrderId, identifier)
          )
        ).limit(1);
      }
    }

    if (!order) {
      return NextResponse.json({ error: "Order not found" }, { status: 404 });
    }

    // Verify with Cashfree API if configured and not a dev mock
    const cfId = cashfreeOrderId || order.cashfreeOrderId;
    const isMockOrder = Boolean(isMock || cfId?.startsWith("order_sim_") || cfId?.startsWith("sim_") || !isCashfreeConfigured());

    if (!isMockOrder && cfId) {
      try {
        const cfOrder = await fetchCashfreeOrder(cfId);
        if (cfOrder.order_status !== "PAID") {
          return NextResponse.json(
            { error: `Payment not completed. Status: ${cfOrder.order_status}` },
            { status: 400 }
          );
        }
      } catch (err: any) {
        console.error("Cashfree order fetch error during verify:", err);
        return NextResponse.json(
          { error: "Failed to verify order status with Cashfree" },
          { status: 400 }
        );
      }
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
      const paymentRef = cashfreePaymentId || `cf_pay_${Date.now()}`;
      await tx.insert(payments).values({
        orderId: order.id,
        cashfreePaymentId: paymentRef,
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
          cashfreePaymentId: paymentRef,
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
