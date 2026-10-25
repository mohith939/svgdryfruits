import { NextResponse } from "next/server";
import { desc, eq, sql } from "drizzle-orm";
import { z } from "zod";
import { getDb } from "@/db/client";
import { auditEvents, coupons, orders } from "@/db/schema";
import { getAdminSession } from "@/lib/auth";

const couponSchema = z.object({
  code: z.string().trim().min(2).max(40).transform((c) => c.toUpperCase()),
  name: z.string().trim().optional().nullable(),
  description: z.string().trim().optional().nullable(),
  type: z.enum(["percent", "percentage", "fixed", "fixed_paise", "free_shipping"]).transform((t) => {
    if (t === "percentage") return "percent";
    if (t === "fixed_paise") return "fixed";
    return t;
  }),
  value: z.number().int().min(0), // percentage (1-100) or paise (0 for free_shipping)
  minSubtotalPaise: z.number().int().min(0).default(0),
  maxDiscountPaise: z.number().int().min(0).optional().nullable(),
  active: z.boolean().default(true),
  usageLimit: z.number().int().min(1).optional().nullable(),
  startsAt: z.string().optional().nullable().transform((d) => (d ? new Date(d) : null)),
  expiresAt: z.string().optional().nullable().transform((d) => (d ? new Date(d) : null)),
});

export async function GET() {
  const session = await getAdminSession();
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const db = await getDb();
  const list = await db.select().from(coupons).orderBy(desc(coupons.code));

  // Calculate per-coupon real analytics from orders
  const orderStats = await db
    .select({
      couponCode: orders.couponCode,
      orderCount: sql<number>`count(*)::int`,
      totalDiscountPaise: sql<number>`coalesce(sum(${orders.discountPaise}), 0)::int`,
    })
    .from(orders)
    .where(sql`${orders.couponCode} IS NOT NULL`)
    .groupBy(orders.couponCode);

  const statsMap = new Map<string, { orderCount: number; totalDiscountPaise: number }>();
  for (const stat of orderStats) {
    if (stat.couponCode) {
      statsMap.set(stat.couponCode.toUpperCase(), {
        orderCount: Number(stat.orderCount) || 0,
        totalDiscountPaise: Number(stat.totalDiscountPaise) || 0,
      });
    }
  }

  const enrichedCoupons = list.map((c) => {
    const stats = statsMap.get(c.code.toUpperCase()) || { orderCount: 0, totalDiscountPaise: 0 };
    return {
      ...c,
      totalOrders: Math.max(c.timesUsed, stats.orderCount),
      totalDiscountPaise: stats.totalDiscountPaise,
    };
  });

  const totalRedemptions = enrichedCoupons.reduce((sum, c) => sum + c.totalOrders, 0);
  const totalSavingsPaise = enrichedCoupons.reduce((sum, c) => sum + c.totalDiscountPaise, 0);
  const activeCouponsCount = enrichedCoupons.filter((c) => c.active).length;

  return NextResponse.json({
    coupons: enrichedCoupons,
    analytics: {
      activeCouponsCount,
      totalRedemptions,
      totalSavingsPaise,
      totalCoupons: enrichedCoupons.length,
    },
  });
}

export async function POST(req: Request) {
  const session = await getAdminSession();
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const raw = await req.json().catch(() => null);
  const parsed = couponSchema.safeParse(raw);
  if (!parsed.success) {
    return NextResponse.json({ error: parsed.error.issues[0]?.message || "Invalid coupon data" }, { status: 400 });
  }

  const db = await getDb();
  const [existing] = await db.select().from(coupons).where(eq(coupons.code, parsed.data.code)).limit(1);
  if (existing) {
    return NextResponse.json({ error: "A coupon with this code already exists." }, { status: 400 });
  }

  const [created] = await db.insert(coupons).values(parsed.data).returning();

  await db.insert(auditEvents).values({
    actor: session.admin.email,
    action: "admin_coupon_created",
    detail: { code: created.code, type: created.type, value: created.value },
  });

  return NextResponse.json({ ok: true, coupon: created });
}

export async function PUT(req: Request) {
  const session = await getAdminSession();
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const raw = await req.json().catch(() => null);
  if (!raw?.id) return NextResponse.json({ error: "Missing coupon ID" }, { status: 400 });

  const patch: Record<string, any> = {};
  if (typeof raw.active === "boolean") patch.active = raw.active;
  if (typeof raw.name === "string") patch.name = raw.name.trim();
  if (typeof raw.description === "string") patch.description = raw.description.trim();
  if (typeof raw.value === "number") patch.value = raw.value;
  if (typeof raw.minSubtotalPaise === "number") patch.minSubtotalPaise = raw.minSubtotalPaise;
  if (raw.usageLimit !== undefined) patch.usageLimit = raw.usageLimit ? Number(raw.usageLimit) : null;
  if (raw.startsAt !== undefined) patch.startsAt = raw.startsAt ? new Date(raw.startsAt) : null;
  if (raw.expiresAt !== undefined) patch.expiresAt = raw.expiresAt ? new Date(raw.expiresAt) : null;

  const db = await getDb();
  const [updated] = await db
    .update(coupons)
    .set(patch)
    .where(eq(coupons.id, raw.id))
    .returning();

  return NextResponse.json({ ok: true, coupon: updated });
}

export async function DELETE(req: Request) {
  const session = await getAdminSession();
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const url = new URL(req.url);
  const id = url.searchParams.get("id");
  if (!id) return NextResponse.json({ error: "Missing coupon ID" }, { status: 400 });

  const db = await getDb();
  const [existing] = await db.select().from(coupons).where(eq(coupons.id, id)).limit(1);
  if (!existing) return NextResponse.json({ error: "Coupon not found" }, { status: 404 });

  await db.delete(coupons).where(eq(coupons.id, id));

  await db.insert(auditEvents).values({
    actor: session.admin.email,
    action: "admin_coupon_deleted",
    detail: { code: existing.code },
  });

  return NextResponse.json({ ok: true, message: `Coupon ${existing.code} deleted.` });
}
