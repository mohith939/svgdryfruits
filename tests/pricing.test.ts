import { describe, expect, it } from "vitest";
import { calculateShipping, calculateTotals, applyCoupon, type CouponInput } from "@/lib/pricing";
import { formatINR, assertPaise } from "@/lib/money";

const ship = { flatChargePaise: 6000, freeThresholdPaise: 100000, active: true };
const coupon: CouponInput = {
  code: "T10", type: "percent", value: 10, minSubtotalPaise: 50000, maxDiscountPaise: 5000,
  active: true, startsAt: null, expiresAt: null, usageLimit: 2, timesUsed: 0,
};

describe("money", () => {
  it("formats and validates paise", () => {
    expect(formatINR(49900)).toBe("₹499");
    expect(() => assertPaise(1.5)).toThrow();
    expect(() => assertPaise(-1)).toThrow();
  });
});

describe("pricing", () => {
  it("charges flat shipping below threshold, free at threshold", () => {
    expect(calculateShipping(99999, ship)).toBe(6000);
    expect(calculateShipping(100000, ship)).toBe(0);
  });
  it("uses regional rate and inactive shipping", () => {
    expect(calculateShipping(1000, { ...ship, regionRates: { KA: 4000 } }, "ka")).toBe(4000);
    expect(calculateShipping(1000, { ...ship, active: false })).toBe(0);
  });
  it("caps and validates coupons", () => {
    expect(applyCoupon(80000, coupon)).toEqual({ ok: true, discountPaise: 5000 });
    expect(applyCoupon(10000, coupon).ok).toBe(false);
    expect(applyCoupon(80000, { ...coupon, timesUsed: 2 }).ok).toBe(false);
    expect(applyCoupon(80000, { ...coupon, expiresAt: new Date(0) }).ok).toBe(false);
  });
  it("computes totals consistently", () => {
    const t = calculateTotals([{ unitPricePaise: 30000, quantity: 2 }], ship, coupon);
    expect(t).toMatchObject({ subtotalPaise: 60000, discountPaise: 5000, shippingPaise: 6000, totalPaise: 61000 });
  });
  it("rejects bad quantities", () => {
    expect(() => calculateTotals([{ unitPricePaise: 100, quantity: 0 }], ship)).toThrow();
  });
});
