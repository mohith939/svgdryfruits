import { NextResponse } from "next/server";
import { eq } from "drizzle-orm";
import { getDb } from "@/db/client";
import { auditEvents, orders } from "@/db/schema";
import { getAdminSession } from "@/lib/auth";
import { dispatchViaShadowfax } from "@/lib/shipping/shadowfax";

export async function POST(
  req: Request,
  { params }: { params: Promise<{ id: string }> }
) {
  const session = await getAdminSession();
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const { id } = await params;
  const db = await getDb();

  const [order] = await db.select().from(orders).where(eq(orders.id, id)).limit(1);
  if (!order) return NextResponse.json({ error: "Order not found" }, { status: 404 });

  const result = await dispatchViaShadowfax({
    orderNumber: order.orderNumber,
    customerName: order.customerName,
    customerPhone: order.customerPhone,
    customerEmail: order.customerEmail,
    shippingAddress: (order.shippingAddress as Record<string, string>) || {},
    totalPaise: order.totalPaise,
    isCod: order.paymentStatus !== "paid",
  });

  const [updated] = await db
    .update(orders)
    .set({
      fulfillmentStatus: "shipped",
      courier: result.courierName,
      trackingNumber: result.awbNumber,
    })
    .where(eq(orders.id, id))
    .returning();

  await db.insert(auditEvents).values({
    actor: session.admin.email,
    action: "admin_order_dispatch_shadowfax",
    detail: {
      orderNumber: order.orderNumber,
      awbNumber: result.awbNumber,
      courier: result.courierName,
    },
  });

  return NextResponse.json({
    ok: true,
    order: updated,
    dispatch: result,
  });
}
