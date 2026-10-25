import { NextResponse } from "next/server";
import { z } from "zod";
import { getDb } from "@/db/client";
import { variants } from "@/db/schema";
import { getAdminSession } from "@/lib/auth";

const variantSchema = z.object({
  productId: z.string().uuid(),
  sku: z.string().trim().min(1),
  label: z.string().trim().min(1),
  pricePaise: z.number().int().min(0),
  comparePricePaise: z.number().int().min(0).optional().nullable(),
  stock: z.number().int().min(0).default(0),
  lowStockThreshold: z.number().int().min(0).default(5),
  active: z.boolean().default(true),
});

export async function POST(req: Request) {
  const session = await getAdminSession();
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const raw = await req.json().catch(() => null);
  const parsed = variantSchema.safeParse(raw);
  if (!parsed.success) {
    return NextResponse.json({ error: parsed.error.issues[0]?.message || "Invalid variant data" }, { status: 400 });
  }

  const db = await getDb();
  try {
    const [created] = await db.insert(variants).values(parsed.data).returning();
    return NextResponse.json({ ok: true, variant: created });
  } catch (err: unknown) {
    console.error("Variant creation error:", err);
    return NextResponse.json({ error: "SKU must be unique or invalid product." }, { status: 400 });
  }
}
