import { NextResponse } from "next/server";
import { eq } from "drizzle-orm";
import crypto from "node:crypto";
import { z } from "zod";
import { getDb } from "@/db/client";
import { categories, orderItems, orders, products, variants } from "@/db/schema";
import { priceCart } from "@/lib/cart-server";
import { InsufficientStockError, reserveStock } from "@/lib/inventory";
import { createGatewayOrder, getRazorpayKeyId } from "@/lib/razorpay";
import { findCatalogVariant, REAL_CATEGORIES_BY_SLUG } from "@/lib/catalog-data";

const checkoutSchema = z.object({
  customer: z.object({
    name: z.string().trim().min(2, "Full name must be at least 2 characters").max(100),
    email: z.string().trim().email("Please enter a valid email").optional().or(z.literal("")),
    phone: z.string().trim().regex(/^[6-9]\d{9}$/, "Please enter a valid 10-digit Indian mobile number"),
    address: z.object({
      street: z.string().trim().min(5, "Address must be at least 5 characters").max(200),
      landmark: z.string().trim().max(100).optional().or(z.literal("")),
      city: z.string().trim().min(2, "City is required").max(80),
      state: z.string().trim().min(2, "State is required").max(80),
      pincode: z.string().trim().regex(/^\d{6}$/, "Please enter a valid 6-digit Indian PIN code"),
    }),
  }),
  lines: z.array(z.object({
    variantId: z.string().uuid(),
    quantity: z.number().int().min(1).max(20),
  })).min(1, "Your bag is empty").max(50),
  couponCode: z.string().trim().max(40).optional(),
});

function generateOrderNumber(): string {
  const d = new Date();
  const ymd = `${d.getFullYear()}${String(d.getMonth() + 1).padStart(2, "0")}${String(d.getDate()).padStart(2, "0")}`;
  const rand = Math.floor(1000 + Math.random() * 9000);
  return `SVG-${ymd}-${rand}`;
}

export async function POST(req: Request) {
  try {
    const raw = await req.json().catch(() => null);
    const parsed = checkoutSchema.safeParse(raw);

    if (!parsed.success) {
      const issue = parsed.error.issues[0]?.message ?? "Invalid checkout information";
      return NextResponse.json({ error: issue }, { status: 400 });
    }

    const { customer, lines, couponCode } = parsed.data;
    const priced = await priceCart(lines, couponCode || undefined);

    if (priced.missing.length > 0) {
      return NextResponse.json({
        error: "One or more items in your bag are no longer available. Please update your bag.",
      }, { status: 400 });
    }

    const low = priced.lines.find((l) => l.available < l.quantity);
    if (low) {
      return NextResponse.json({
        error: `${low.productName} (${low.variantLabel}) only has ${low.available} available. Please adjust quantity.`,
      }, { status: 400 });
    }

    if (priced.totals.totalPaise <= 0) {
      return NextResponse.json({ error: "Cart total must be greater than zero." }, { status: 400 });
    }

    const orderNumber = generateOrderNumber();
    const trackingToken = crypto.randomBytes(16).toString("hex");
    const reservationExpiresAt = new Date(Date.now() + 15 * 60_000); // 15 mins TTL

    // Create Razorpay order first
    const gatewayOrder = await createGatewayOrder({
      amountPaise: priced.totals.totalPaise,
      receipt: orderNumber,
      notes: { orderNumber, customerPhone: customer.phone },
    });

    const db = await getDb();

    // Ensure referenced variants exist in database (e.g. fresh lambda cold start)
    for (const line of priced.lines) {
      const [existingVar] = await db.select().from(variants).where(eq(variants.id, line.variantId)).limit(1);
      if (!existingVar) {
        const catalogItem = findCatalogVariant(line.variantId);
        if (catalogItem) {
          const cat = REAL_CATEGORIES_BY_SLUG.get(catalogItem.product.categorySlug);
          if (cat) {
            await db.insert(categories).values({
              id: cat.id,
              slug: cat.slug,
              name: cat.name,
              description: cat.description,
              imageUrl: cat.imageUrl,
              sortOrder: cat.sortOrder,
              active: true,
            }).onConflictDoNothing();
          }
          await db.insert(products).values({
            id: catalogItem.product.id,
            slug: catalogItem.product.slug,
            name: catalogItem.product.name,
            description: catalogItem.product.description,
            categoryId: cat?.id ?? null,
            imageUrl: catalogItem.product.imageUrl,
            isCombo: catalogItem.product.isCombo,
            active: true,
          }).onConflictDoNothing();
          await db.insert(variants).values({
            id: catalogItem.variant.id,
            productId: catalogItem.product.id,
            sku: catalogItem.variant.sku,
            label: catalogItem.variant.label,
            pricePaise: catalogItem.variant.pricePaise,
            comparePricePaise: catalogItem.variant.comparePricePaise,
            stock: catalogItem.variant.stock,
            active: true,
          }).onConflictDoNothing();
        }
      }
    }

    // Insert order in pending state
    const [createdOrder] = await db.insert(orders).values({
      orderNumber,
      trackingToken,
      paymentStatus: "pending",
      fulfillmentStatus: "awaiting_payment",
      customerName: customer.name,
      customerEmail: customer.email || null,
      customerPhone: customer.phone,
      shippingAddress: customer.address,
      subtotalPaise: priced.totals.subtotalPaise,
      discountPaise: priced.totals.discountPaise,
      shippingPaise: priced.totals.shippingPaise,
      totalPaise: priced.totals.totalPaise,
      couponCode: couponCode || null,
      razorpayOrderId: gatewayOrder.id,
      reservationExpiresAt,
    }).returning();

    // Snapshot order items
    for (const line of priced.lines) {
      await db.insert(orderItems).values({
        orderId: createdOrder.id,
        variantId: line.variantId,
        productName: line.productName,
        variantLabel: line.variantLabel,
        unitPricePaise: line.unitPricePaise,
        quantity: line.quantity,
      });
    }

    // Atomically reserve inventory with expiration
    try {
      await reserveStock(db, createdOrder.id, lines, 15 * 60_000);
    } catch (err) {
      // If stock check fails under concurrent checkout, clean up and return conflict
      await db.delete(orders).where(eq(orders.id, createdOrder.id));
      if (err instanceof InsufficientStockError) {
        return NextResponse.json({
          error: "A product just went out of stock during checkout. Please check your bag.",
        }, { status: 409 });
      }
      throw err;
    }

    const keyId = getRazorpayKeyId();

    return NextResponse.json({
      orderId: createdOrder.id,
      orderNumber: createdOrder.orderNumber,
      trackingToken: createdOrder.trackingToken,
      razorpayOrderId: gatewayOrder.id,
      amountPaise: priced.totals.totalPaise,
      currency: "INR",
      keyId,
      isMock: gatewayOrder.isMock,
      customer: {
        name: customer.name,
        email: customer.email,
        phone: customer.phone,
      },
    });
  } catch (err: unknown) {
    console.error("Checkout creation error:", err);
    return NextResponse.json({ error: "Unable to initiate checkout. Please try again." }, { status: 500 });
  }
}
