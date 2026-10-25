import { and, eq, inArray, sql } from "drizzle-orm";
import type { Db } from "@/db/client";
import { comboItems, reservations, variants } from "@/db/schema";

export class InsufficientStockError extends Error {
  constructor(public variantId: string) {
    super("Insufficient stock");
  }
}

export type ReserveLine = { variantId: string; quantity: number };

// Expands combo variants into their component variants so combos consume component stock.
async function expand(tx: Db, lines: ReserveLine[]): Promise<Map<string, number>> {
  const need = new Map<string, number>();
  const add = (v: string, q: number) => need.set(v, (need.get(v) ?? 0) + q);
  const combos = await tx.select().from(comboItems).where(inArray(comboItems.comboVariantId, lines.map((l) => l.variantId)));
  for (const l of lines) {
    const parts = combos.filter((c) => c.comboVariantId === l.variantId);
    if (parts.length) parts.forEach((p) => add(p.componentVariantId, p.quantity * l.quantity));
    else add(l.variantId, l.quantity);
  }
  return need;
}

// Atomically holds stock for an order. Rows are locked in id order to avoid deadlocks.
export async function reserveStock(db: Db, orderId: string, lines: ReserveLine[], ttlMs = 15 * 60_000, now = new Date()) {
  for (const l of lines) if (!Number.isInteger(l.quantity) || l.quantity < 1) throw new Error("Invalid quantity");
  await db.transaction(async (tx) => {
    const need = await expand(tx as unknown as Db, lines);
    const ids = [...need.keys()].sort();
    const rows = await tx.select().from(variants).where(inArray(variants.id, ids)).orderBy(variants.id).for("update");
    const byId = new Map(rows.map((r) => [r.id, r]));
    const expiresAt = new Date(now.getTime() + ttlMs);
    for (const vid of ids) {
      const v = byId.get(vid);
      if (!v || !v.active) throw new InsufficientStockError(vid);
      const [{ held }] = await tx
        .select({ held: sql<number>`coalesce(sum(${reservations.quantity}),0)::int` })
        .from(reservations)
        .where(and(eq(reservations.variantId, vid), eq(reservations.status, "held"), sql`${reservations.expiresAt} > ${now}`));
      if (v.stock - held < need.get(vid)!) throw new InsufficientStockError(vid);
      await tx.insert(reservations).values({ orderId, variantId: vid, quantity: need.get(vid)!, expiresAt });
    }
  });
}

export async function releaseReservations(db: Db, orderId: string) {
  await db.update(reservations).set({ status: "released" }).where(and(eq(reservations.orderId, orderId), eq(reservations.status, "held")));
}

// Converts held reservations into a permanent stock deduction. Idempotent.
export async function commitReservations(db: Db, orderId: string) {
  await db.transaction(async (tx) => {
    const held = await tx.update(reservations).set({ status: "committed" })
      .where(and(eq(reservations.orderId, orderId), eq(reservations.status, "held"))).returning();
    for (const r of held) {
      await tx.update(variants).set({ stock: sql`${variants.stock} - ${r.quantity}` }).where(eq(variants.id, r.variantId));
    }
  });
}
