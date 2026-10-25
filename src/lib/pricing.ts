import { assertPaise } from "./money";

export type ShippingConfig = {
  flatChargePaise: number;
  freeThresholdPaise: number | null;
  regionRates?: Record<string, number>;
  active: boolean;
};

export type CouponInput = {
  code: string;
  type: "percent" | "fixed";
  value: number; // percent (1-100) or paise
  minSubtotalPaise: number;
  maxDiscountPaise: number | null;
  active: boolean;
  startsAt: Date | null;
  expiresAt: Date | null;
  usageLimit: number | null;
  timesUsed: number;
};

export type CouponResult =
  | { ok: true; discountPaise: number }
  | { ok: false; reason: string };

export function calculateShipping(
  subtotalAfterDiscountPaise: number,
  cfg: ShippingConfig,
  region?: string,
): number {
  if (!cfg.active) return 0;
  if (cfg.freeThresholdPaise !== null && subtotalAfterDiscountPaise >= cfg.freeThresholdPaise) return 0;
  const regional = region ? cfg.regionRates?.[region.toUpperCase()] : undefined;
  return assertPaise(regional ?? cfg.flatChargePaise);
}

export function applyCoupon(subtotalPaise: number, c: CouponInput, now = new Date()): CouponResult {
  if (!c.active) return { ok: false, reason: "Coupon is not active" };
  if (c.startsAt && now < c.startsAt) return { ok: false, reason: "Coupon is not yet valid" };
  if (c.expiresAt && now > c.expiresAt) return { ok: false, reason: "Coupon has expired" };
  if (c.usageLimit !== null && c.timesUsed >= c.usageLimit) return { ok: false, reason: "Coupon usage limit reached" };
  if (subtotalPaise < c.minSubtotalPaise) return { ok: false, reason: "Minimum order value not met" };
  let discount = c.type === "percent" ? Math.floor((subtotalPaise * c.value) / 100) : c.value;
  if (c.maxDiscountPaise !== null) discount = Math.min(discount, c.maxDiscountPaise);
  discount = Math.min(discount, subtotalPaise);
  return { ok: true, discountPaise: assertPaise(discount) };
}

export type LineInput = { unitPricePaise: number; quantity: number };

export function calculateTotals(
  lines: LineInput[],
  shipping: ShippingConfig,
  coupon?: CouponInput,
  region?: string,
  now = new Date(),
) {
  const subtotalPaise = lines.reduce((s, l) => {
    if (!Number.isInteger(l.quantity) || l.quantity < 1) throw new Error("Invalid quantity");
    return s + assertPaise(l.unitPricePaise) * l.quantity;
  }, 0);
  let discountPaise = 0;
  let couponError: string | null = null;
  if (coupon) {
    const r = applyCoupon(subtotalPaise, coupon, now);
    if (r.ok) discountPaise = r.discountPaise;
    else couponError = r.reason;
  }
  const shippingPaise = calculateShipping(subtotalPaise - discountPaise, shipping, region);
  return {
    subtotalPaise,
    discountPaise,
    shippingPaise,
    totalPaise: subtotalPaise - discountPaise + shippingPaise,
    couponError,
  };
}
