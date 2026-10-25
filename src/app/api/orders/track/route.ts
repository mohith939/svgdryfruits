import { NextResponse } from "next/server";
import { desc, eq, ilike, or } from "drizzle-orm";
import { z } from "zod";
import { getDb } from "@/db/client";
import { orderItems, orders } from "@/db/schema";

const querySchema = z.object({
  orderNumber: z.string().trim().max(50).optional().nullable(),
  token: z.string().trim().max(80).optional().nullable(),
  phone: z.string().trim().max(20).optional().nullable(),
});

export async function POST(req: Request) {
  try {
    const raw = await req.json().catch(() => null);
    const parsed = querySchema.safeParse(raw);

    if (!parsed.success) {
      return NextResponse.json(
        { error: "Please enter a valid order number or phone number." },
        { status: 400 }
      );
    }

    const { orderNumber, token, phone } = parsed.data;

    if (!orderNumber && !phone) {
      return NextResponse.json(
        { error: "Please enter either your Order Number or 10-digit mobile number." },
        { status: 400 }
      );
    }

    const db = await getDb();
    let order = null;

    if (orderNumber) {
      // Normalize order number (strip extra whitespace, uppercase, tolerate missing SVG- prefix)
      const cleanNum = orderNumber.trim().toUpperCase();
      const withPrefix = cleanNum.startsWith("SVG-") ? cleanNum : `SVG-${cleanNum}`;

      const [found] = await db
        .select()
        .from(orders)
        .where(or(eq(orders.orderNumber, cleanNum), eq(orders.orderNumber, withPrefix)))
        .limit(1);

      order = found;

      // If token was supplied and doesn't match, block
      if (order && token && order.trackingToken !== token) {
        return NextResponse.json(
          { error: "Order details could not be verified with the provided tracking link." },
          { status: 403 }
        );
      }

      // If phone was supplied and doesn't match, block
      const cleanPhone = phone?.replace(/\D/g, "");
      if (order && cleanPhone) {
        const orderPhoneClean = order.customerPhone.replace(/\D/g, "");
        if (!orderPhoneClean.endsWith(cleanPhone) && !cleanPhone.endsWith(orderPhoneClean)) {
          return NextResponse.json(
            { error: "The mobile number entered does not match this order." },
            { status: 403 }
          );
        }
      }
    } else if (phone) {
      // Search by phone alone (retrieve the latest order for this customer)
      const cleanPhone = phone.replace(/\D/g, "");
      if (cleanPhone.length < 10) {
        return NextResponse.json(
          { error: "Please enter a valid 10-digit Indian mobile number." },
          { status: 400 }
        );
      }

      const matchingOrders = await db
        .select()
        .from(orders)
        .orderBy(desc(orders.createdAt))
        .limit(20);

      order = matchingOrders.find((o) => {
        const oClean = o.customerPhone.replace(/\D/g, "");
        return oClean.endsWith(cleanPhone) || cleanPhone.endsWith(oClean);
      });
    }

    if (!order) {
      return NextResponse.json(
        { error: "No matching order found. Please verify your order number or phone number." },
        { status: 404 }
      );
    }

    const items = await db
      .select({
        productName: orderItems.productName,
        variantLabel: orderItems.variantLabel,
        quantity: orderItems.quantity,
      })
      .from(orderItems)
      .where(eq(orderItems.orderId, order.id));

    const addr = (order.shippingAddress || {}) as {
      city?: string;
      state?: string;
      pincode?: string;
    };

    return NextResponse.json({
      orderNumber: order.orderNumber,
      createdAt: order.createdAt,
      paymentStatus: order.paymentStatus,
      fulfillmentStatus: order.fulfillmentStatus,
      courier: order.courier,
      trackingNumber: order.trackingNumber,
      destination:
        addr.city && addr.state ? `${addr.city}, ${addr.state} (${addr.pincode ?? ""})` : null,
      items,
    });
  } catch (err: unknown) {
    console.error("Tracking API error:", err);
    return NextResponse.json(
      { error: "Unable to retrieve order tracking at this moment." },
      { status: 500 }
    );
  }
}
