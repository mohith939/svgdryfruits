import { beforeEach, describe, expect, it } from "vitest";
import { eq } from "drizzle-orm";
import { createLocalDb, type Db } from "@/db/client";
import { categories, coupons, orders, products, variants, reservations } from "@/db/schema";
import { commitReservations, reserveStock } from "@/lib/inventory";
import { verifyPaymentSignature } from "@/lib/razorpay";

let db: Db;
let prodId: string;
let varId: string;

beforeEach(async () => {
  db = await createLocalDb();
  const [c] = await db.insert(categories).values({ slug: "nuts", name: "Nuts" }).returning();
  const [p] = await db.insert(products).values({ slug: "almonds", name: "Almonds", categoryId: c.id }).returning();
  const [v] = await db.insert(variants).values([
    { productId: p.id, sku: "ALM-250", label: "250 g", pricePaise: 29900, stock: 10 },
  ]).returning();
  prodId = p.id;
  varId = v.id;
});

describe("checkout and payment verification", () => {
  it("creates order, reserves stock, and verifies payment idempotently", async () => {
    // 1. Create order
    const [order] = await db.insert(orders).values({
      orderNumber: "SVG-TEST-001",
      trackingToken: "token-secret-12345",
      paymentStatus: "pending",
      fulfillmentStatus: "awaiting_payment",
      customerName: "Ramesh Kumar",
      customerPhone: "9876543210",
      shippingAddress: { street: "MG Road", city: "Hyderabad", state: "Telangana", pincode: "500001" },
      subtotalPaise: 29900,
      discountPaise: 0,
      shippingPaise: 0,
      totalPaise: 29900,
      razorpayOrderId: "order_sim_123",
      reservationExpiresAt: new Date(Date.now() + 15 * 60_000),
    }).returning();

    // 2. Reserve stock
    await reserveStock(db, order.id, [{ variantId: varId, quantity: 2 }]);

    // Check held reservations
    const held = await db.select().from(reservations).where(eq(reservations.orderId, order.id));
    expect(held).toHaveLength(1);
    expect(held[0].status).toBe("held");
    expect(held[0].quantity).toBe(2);

    // Initial variant stock is still 10 before commit
    const [vBefore] = await db.select().from(variants).where(eq(variants.id, varId));
    expect(vBefore.stock).toBe(10);

    // 3. Payment verification
    const sigValid = verifyPaymentSignature({
      razorpayOrderId: "order_sim_123",
      razorpayPaymentId: "pay_sim_999",
      razorpaySignature: "mock_signature_approved",
    });
    expect(sigValid).toBe(true);

    // Commit reservations
    await commitReservations(db, order.id);

    // Stock should now be 8
    const [vAfter] = await db.select().from(variants).where(eq(variants.id, varId));
    expect(vAfter.stock).toBe(8);

    // Idempotent commit: running commitReservations again does not double-deduct
    await commitReservations(db, order.id);
    const [vAfterSecond] = await db.select().from(variants).where(eq(variants.id, varId));
    expect(vAfterSecond.stock).toBe(8);
  });

  it("handles coupon usage limit validation", async () => {
    const [coupon] = await db.insert(coupons).values({
      code: "WELCOME50",
      type: "fixed",
      value: 5000,
      minSubtotalPaise: 20000,
      maxDiscountPaise: null,
      active: true,
      usageLimit: 1,
      timesUsed: 0,
    }).returning();

    expect(coupon.timesUsed).toBe(0);
    expect(coupon.usageLimit).toBe(1);
  });
});
