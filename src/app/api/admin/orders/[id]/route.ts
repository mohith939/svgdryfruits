import { NextResponse } from "next/server";
import { desc, eq } from "drizzle-orm";
import { z } from "zod";
import { getDb } from "@/db/client";
import { auditEvents, orderItems, orders, payments, refunds } from "@/db/schema";
import { getAdminSession } from "@/lib/auth";

const updateOrderSchema = z.object({
  fulfillmentStatus: z.enum(["awaiting_payment", "confirmed", "processing", "packed", "shipped", "delivered", "cancelled"]).optional(),
  courier: z.string().trim().optional().nullable(),
  trackingNumber: z.string().trim().optional().nullable(),
});

export async function GET(
  req: Request,
  { params }: { params: Promise<{ id: string }> }
) {
  const session = await getAdminSession();
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const { id } = await params;
  const db = await getDb();

  const [order] = await db.select().from(orders).where(eq(orders.id, id)).limit(1);
  if (!order) return NextResponse.json({ error: "Order not found" }, { status: 404 });

  const items = await db.select().from(orderItems).where(eq(orderItems.orderId, id));
  const orderPayments = await db.select().from(payments).where(eq(payments.orderId, id));
  const orderRefunds = await db.select().from(refunds).where(eq(refunds.orderId, id)).orderBy(desc(refunds.createdAt));

  return NextResponse.json({
    order,
    items,
    payments: orderPayments,
    refunds: orderRefunds,
  });
}

export async function PUT(
  req: Request,
  { params }: { params: Promise<{ id: string }> }
) {
  const session = await getAdminSession();
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const { id } = await params;
  const raw = await req.json().catch(() => null);
  const parsed = updateOrderSchema.safeParse(raw);

  if (!parsed.success) {
    return NextResponse.json({ error: "Invalid order update values" }, { status: 400 });
  }

  const db = await getDb();
  const [order] = await db.select().from(orders).where(eq(orders.id, id)).limit(1);
  if (!order) return NextResponse.json({ error: "Order not found" }, { status: 404 });

  const [updated] = await db
    .update(orders)
    .set(parsed.data)
    .where(eq(orders.id, id))
    .returning();

  // Audit status change
  await db.insert(auditEvents).values({
    actor: session.admin.email,
    action: "admin_order_update",
    detail: {
      orderNumber: order.orderNumber,
      changes: parsed.data,
    },
  });

  return NextResponse.json({ ok: true, order: updated });
}
