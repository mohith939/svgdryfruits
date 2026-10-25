import { NextResponse } from "next/server";
import { and, desc, eq, ilike, or } from "drizzle-orm";
import { getDb } from "@/db/client";
import { orders } from "@/db/schema";
import { getAdminSession } from "@/lib/auth";

export async function GET(req: Request) {
  const session = await getAdminSession();
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const url = new URL(req.url);
  const q = url.searchParams.get("q")?.trim();
  const payment = url.searchParams.get("payment")?.trim();
  const fulfillment = url.searchParams.get("fulfillment")?.trim();

  const conds = [];
  if (q) {
    const like = `%${q}%`;
    conds.push(
      or(
        ilike(orders.orderNumber, like),
        ilike(orders.customerName, like),
        ilike(orders.customerPhone, like)
      )!
    );
  }
  if (payment) conds.push(eq(orders.paymentStatus, payment));
  if (fulfillment) conds.push(eq(orders.fulfillmentStatus, fulfillment));

  const db = await getDb();
  const rows = await db
    .select()
    .from(orders)
    .where(conds.length ? and(...conds) : undefined)
    .orderBy(desc(orders.createdAt))
    .limit(100);

  return NextResponse.json({ orders: rows });
}

export async function POST(req: Request) {
  const session = await getAdminSession();
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const body = await req.json().catch(() => null);
  if (!body) return NextResponse.json({ error: "Invalid payload" }, { status: 400 });

  const {
    customerName,
    customerEmail,
    customerPhone,
    addressLine1,
    city,
    state,
    pincode,
    items,
    paymentStatus = "paid",
    fulfillmentStatus = "pending",
  } = body;

  if (!customerName?.trim() || !customerPhone?.trim()) {
    return NextResponse.json({ error: "Customer name and phone are required" }, { status: 400 });
  }

  const db = await getDb();
  const orderNumber = "SVG" + Math.floor(1000 + Math.random() * 9000);
  const trackingToken = "trk_" + Math.random().toString(36).substring(2, 10);

  let subtotalPaise = 0;
  const itemsToInsert = Array.isArray(items) && items.length > 0 ? items : [
    {
      productName: "California Almonds",
      variantLabel: "500 g",
      unitPricePaise: 67900,
      quantity: 1,
    }
  ];

  for (const it of itemsToInsert) {
    subtotalPaise += (Number(it.unitPricePaise) || 50000) * (Number(it.quantity) || 1);
  }

  const [order] = await db
    .insert(orders)
    .values({
      orderNumber,
      trackingToken,
      paymentStatus: paymentStatus || "paid",
      fulfillmentStatus: fulfillmentStatus || "pending",
      customerName: customerName.trim(),
      customerEmail: customerEmail?.trim() || null,
      customerPhone: customerPhone.trim(),
      shippingAddress: {
        line1: addressLine1 || "Indiranagar",
        city: city || "Bengaluru",
        state: state || "Karnataka",
        pincode: pincode || "560038",
      },
      subtotalPaise,
      discountPaise: 0,
      shippingPaise: 0,
      totalPaise: subtotalPaise,
    })
    .returning();

  return NextResponse.json({ ok: true, order });
}

