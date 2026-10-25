import { beforeEach, describe, expect, it } from "vitest";
import { eq } from "drizzle-orm";
import { createLocalDb, type Db } from "@/db/client";
import { categories, comboItems, orders, products, variants } from "@/db/schema";
import { commitReservations, InsufficientStockError, releaseReservations, reserveStock } from "@/lib/inventory";

let db: Db;
let a: string, b: string, combo: string;

async function mkOrder(n: string) {
  const [o] = await db.insert(orders).values({
    orderNumber: n, trackingToken: n + "-tok", customerName: "T", customerPhone: "0", shippingAddress: {},
    subtotalPaise: 1, totalPaise: 1,
  }).returning();
  return o.id;
}

beforeEach(async () => {
  db = await createLocalDb();
  const [c] = await db.insert(categories).values({ slug: "c", name: "C" }).returning();
  const [p] = await db.insert(products).values({ slug: "p", name: "P", categoryId: c.id }).returning();
  const [pc] = await db.insert(products).values({ slug: "pc", name: "Combo", isCombo: true }).returning();
  const vs = await db.insert(variants).values([
    { productId: p.id, sku: "A", label: "250g", pricePaise: 100, stock: 5 },
    { productId: p.id, sku: "B", label: "500g", pricePaise: 200, stock: 3 },
    { productId: pc.id, sku: "COMBO", label: "Box", pricePaise: 300, stock: 0 },
  ]).returning();
  [a, b, combo] = vs.map((v) => v.id);
  await db.insert(comboItems).values([
    { comboVariantId: combo, componentVariantId: a, quantity: 1 },
    { comboVariantId: combo, componentVariantId: b, quantity: 2 },
  ]);
});

describe("inventory", () => {
  it("never oversells under concurrent reservations", async () => {
    const ids = await Promise.all(Array.from({ length: 8 }, (_, i) => mkOrder("O" + i)));
    const results = await Promise.allSettled(ids.map((id) => reserveStock(db, id, [{ variantId: a, quantity: 1 }])));
    expect(results.filter((r) => r.status === "fulfilled")).toHaveLength(5);
    const rejected = results.filter((r): r is PromiseRejectedResult => r.status === "rejected");
    expect(rejected.every((r) => r.reason instanceof InsufficientStockError)).toBe(true);
  });

  it("release frees stock, expired holds do not count", async () => {
    const o1 = await mkOrder("R1");
    await reserveStock(db, o1, [{ variantId: b, quantity: 3 }]);
    await expect(reserveStock(db, await mkOrder("R2"), [{ variantId: b, quantity: 1 }])).rejects.toBeInstanceOf(InsufficientStockError);
    await releaseReservations(db, o1);
    await reserveStock(db, await mkOrder("R3"), [{ variantId: b, quantity: 3 }]);
    await expect(
      reserveStock(db, await mkOrder("R4"), [{ variantId: a, quantity: 5 }], 1000, new Date(Date.now() - 60_000)),
    ).resolves.toBeUndefined();
    await reserveStock(db, await mkOrder("R5"), [{ variantId: a, quantity: 5 }]);
  });

  it("commit deducts stock once (idempotent)", async () => {
    const o = await mkOrder("C1");
    await reserveStock(db, o, [{ variantId: a, quantity: 2 }]);
    await commitReservations(db, o);
    await commitReservations(db, o);
    const [v] = await db.select().from(variants).where(eq(variants.id, a));
    expect(v.stock).toBe(3);
  });

  it("combos consume component stock", async () => {
    await reserveStock(db, await mkOrder("B1"), [{ variantId: combo, quantity: 1 }]);
    await expect(reserveStock(db, await mkOrder("B2"), [{ variantId: combo, quantity: 1 }])).rejects.toBeInstanceOf(InsufficientStockError);
  });
});
