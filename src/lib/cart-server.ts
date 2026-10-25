import { eq, inArray } from "drizzle-orm";
import { getDb } from "@/db/client";
import { coupons, products, shippingSettings, variants } from "@/db/schema";
import { calculateTotals, type ShippingConfig } from "@/lib/pricing";

export type PricedCart = Awaited<ReturnType<typeof priceCart>>;

// Single server-side source of truth for prices, stock and totals (never trusts client prices).
export async function priceCart(input: { variantId: string; quantity: number }[], couponCode?: string) {
  const db = await getDb();
  const merged = new Map<string, number>();
  for (const l of input) merged.set(l.variantId, (merged.get(l.variantId) ?? 0) + l.quantity);
  const ids = [...merged.keys()];
  const isUuid = (str: string) => /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(str);
  const uuidIds = ids.filter(isUuid);
  const nonUuidIds = ids.filter((id) => !isUuid(id));

  let rows = uuidIds.length
    ? await db.select({ v: variants, p: products }).from(variants).innerJoin(products, eq(products.id, variants.productId)).where(inArray(variants.id, uuidIds))
    : [];

  // Also support matching by SKU if non-uuid IDs (e.g. legacy or SKU fallback)
  if (nonUuidIds.length > 0) {
    const skuRows = await db
      .select({ v: variants, p: products })
      .from(variants)
      .innerJoin(products, eq(products.id, variants.productId))
      .where(inArray(variants.sku, nonUuidIds.map((s) => s.toUpperCase())));
    rows = [...rows, ...skuRows];
  }

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
  const [s] = await db.select().from(shippingSettings);
  const shipping: ShippingConfig = {
    flatChargePaise: s?.flatChargePaise ?? 0, freeThresholdPaise: s?.freeThresholdPaise ?? null, active: s?.active ?? false,
  };
  let coupon;
  if (couponCode) {
    coupon = (await db.select().from(coupons)).find((c) => c.code.toLowerCase() === couponCode.trim().toLowerCase());
  }
  const totals = calculateTotals(lines, shipping, coupon && { ...coupon, type: coupon.type as "percent" | "fixed" }, undefined);
  const missing = ids.filter((id) => !lines.some((l) => l.variantId === id));
  return { lines, totals, missing, freeThresholdPaise: shipping.active ? shipping.freeThresholdPaise : null, couponError: couponCode && !coupon ? "Coupon not found" : totals.couponError };
}
