import { eq, inArray } from "drizzle-orm";
import { getDb } from "@/db/client";
import { coupons, products, shippingSettings, variants } from "@/db/schema";
import { calculateTotals, type ShippingConfig } from "@/lib/pricing";
import { findCatalogVariant, REAL_VARIANTS_BY_ID, REAL_VARIANTS_BY_SKU } from "@/lib/catalog-data";

export type PricedCart = Awaited<ReturnType<typeof priceCart>>;

// Single server-side source of truth for prices, stock and totals (never trusts client prices).
export async function priceCart(input: { variantId: string; quantity: number }[], couponCode?: string) {
  const merged = new Map<string, number>();
  for (const l of input) merged.set(l.variantId, (merged.get(l.variantId) ?? 0) + l.quantity);
  const ids = [...merged.keys()];
  const isUuid = (str: string) => /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(str);
  const uuidIds = ids.filter(isUuid);
  const nonUuidIds = ids.filter((id) => !isUuid(id));

  let rows: { v: typeof variants.$inferSelect; p: typeof products.$inferSelect }[] = [];
  let s: typeof shippingSettings.$inferSelect | undefined;
  let allCoupons: (typeof coupons.$inferSelect)[] = [];

  try {
    const db = await getDb();
    if (uuidIds.length > 0) {
      rows = await db
        .select({ v: variants, p: products })
        .from(variants)
        .innerJoin(products, eq(products.id, variants.productId))
        .where(inArray(variants.id, uuidIds));
    }

    if (nonUuidIds.length > 0) {
      const skuRows = await db
        .select({ v: variants, p: products })
        .from(variants)
        .innerJoin(products, eq(products.id, variants.productId))
        .where(inArray(variants.sku, nonUuidIds.map((s) => s.toUpperCase())));
      rows = [...rows, ...skuRows];
    }

    const [shippingRow] = await db.select().from(shippingSettings);
    s = shippingRow;

    if (couponCode) {
      allCoupons = await db.select().from(coupons);
    }
  } catch (dbErr) {
    console.warn("priceCart db lookup fallback:", dbErr);
  }

  // Build lines from DB rows
  const lines = rows.map(({ v, p }) => ({
    variantId: v.id,
    productSlug: p.slug,
    imageUrl: p.imageUrl,
    productName: p.name,
    variantLabel: v.label,
    unitPricePaise: v.pricePaise,
    quantity: merged.get(v.id) ?? merged.get(v.sku) ?? 1,
    available: v.active && p.active ? v.stock : 0,
  }));

  // Resolve any items not found in DB against real catalog-data.ts (e.g. serverless cold start or unseeded DB)
  for (const id of ids) {
    const alreadyFound = lines.some((l) => l.variantId === id);
    if (!alreadyFound) {
      const catalogItem = findCatalogVariant(id);
      if (catalogItem) {
        lines.push({
          variantId: id, // Keep client variant ID so client match is preserved
          productSlug: catalogItem.product.slug,
          imageUrl: catalogItem.product.imageUrl,
          productName: catalogItem.product.name,
          variantLabel: catalogItem.variant.label,
          unitPricePaise: catalogItem.variant.pricePaise,
          quantity: merged.get(id) ?? 1,
          available: catalogItem.variant.stock,
        });
      }
    }
  }

  const shipping: ShippingConfig = {
    flatChargePaise: s?.flatChargePaise ?? 0,
    freeThresholdPaise: s?.freeThresholdPaise ?? 100000,
    active: s?.active ?? false,
  };

  let coupon;
  if (couponCode) {
    coupon = allCoupons.find((c) => c.code.toLowerCase() === couponCode.trim().toLowerCase());
    // Also support fallback standard coupons if DB is unseeded
    if (!coupon && couponCode.toUpperCase() === "WELCOME10") {
      coupon = {
        id: "c0000000-0000-0000-0000-000000000001",
        code: "WELCOME10",
        name: "Welcome Offer",
        description: "10% off on your order",
        type: "percent",
        value: 10,
        minSubtotalPaise: 50000,
        maxDiscountPaise: 20000,
        active: true,
        startsAt: null,
        expiresAt: null,
        timesUsed: 0,
        usageLimit: null,
      };
    }
  }

  const totals = calculateTotals(lines, shipping, coupon && { ...coupon, type: coupon.type as "percent" | "fixed" }, undefined);
  const missing = ids.filter((id) => !lines.some((l) => l.variantId === id));

  return {
    lines,
    totals,
    missing,
    freeThresholdPaise: shipping.active ? shipping.freeThresholdPaise : null,
    couponError: couponCode && !coupon ? "Coupon not found" : totals.couponError,
  };
}
