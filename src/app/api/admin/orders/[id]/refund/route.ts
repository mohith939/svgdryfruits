import { NextResponse } from "next/server";
import { eq, sql } from "drizzle-orm";
import { z } from "zod";
import { getDb } from "@/db/client";
import { auditEvents, inventoryMovements, orderItems, orders, refunds, variants } from "@/db/schema";
import { getAdminSession } from "@/lib/auth";

const refundSchema = z.object({
  amountPaise: z.number().int().min(1),
  note: z.string().trim().min(2, "Please provide a reason or Razorpay gateway reference"),
  restock: z.boolean().default(false),
  markRefunded: z.boolean().default(false),
});

export async function POST(
  req: Request,
  { params }: { params: Promise<{ id: string }> }
) {
  const session = await getAdminSession();
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const { id } = await params;
  const raw = await req.json().catch(() => null);
  const parsed = refundSchema.safeParse(raw);

  if (!parsed.success) {
    return NextResponse.json({ error: parsed.error.issues[0]?.message || "Invalid refund data" }, { status: 400 });
  }

  const { amountPaise, note, restock, markRefunded } = parsed.data;
  const db = await getDb();

  const [order] = await db.select().from(orders).where(eq(orders.id, id)).limit(1);
  if (!order) return NextResponse.json({ error: "Order not found" }, { status: 404 });

  await db.transaction(async (tx) => {
    // 1. Record manual refund
    await tx.insert(refunds).values({
      orderId: order.id,
      amountPaise,
      note,
    });

    // 2. Restock inventory if requested
    if (restock) {
      const items = await tx.select().from(orderItems).where(eq(orderItems.orderId, order.id));
      for (const item of items) {
        if (!item.variantId) continue;
        await tx
          .update(variants)
          .set({ stock: sql`${variants.stock} + ${item.quantity}` })
          .where(eq(variants.id, item.variantId));

        await tx.insert(inventoryMovements).values({
          variantId: item.variantId,
          delta: item.quantity,
          reason: `Restocked on refund / cancellation for order ${order.orderNumber}`,
          orderId: order.id,
        });
      }
    }

    // 3. Update payment status if marked fully refunded
    if (markRefunded) {
      await tx.update(orders).set({ paymentStatus: "refunded", fulfillmentStatus: "cancelled" }).where(eq(orders.id, order.id));
    }

    // 4. Audit event
    await tx.insert(auditEvents).values({
      actor: session.admin.email,
      action: "record_manual_refund",
      detail: {
        orderNumber: order.orderNumber,
        amountPaise,
        note,
        restocked: restock,
      },
    });
  });

  return NextResponse.json({ ok: true, message: "Manual refund recorded successfully." });
}
