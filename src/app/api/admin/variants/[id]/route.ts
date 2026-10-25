import { NextResponse } from "next/server";
import { eq } from "drizzle-orm";
import { z } from "zod";
import { getDb } from "@/db/client";
import { variants } from "@/db/schema";
import { getAdminSession } from "@/lib/auth";

const updateVariantSchema = z.object({
  label: z.string().trim().min(1).optional(),
  sku: z.string().trim().min(1).optional(),
  pricePaise: z.number().int().min(0).optional(),
  comparePricePaise: z.number().int().min(0).optional().nullable(),
  stock: z.number().int().min(0).optional(),
  lowStockThreshold: z.number().int().min(0).optional(),
  active: z.boolean().optional(),
});

export async function PUT(
  req: Request,
  { params }: { params: Promise<{ id: string }> }
) {
  const session = await getAdminSession();
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const { id } = await params;
  const raw = await req.json().catch(() => null);
  const parsed = updateVariantSchema.safeParse(raw);

  if (!parsed.success) {
    return NextResponse.json({ error: parsed.error.issues[0]?.message || "Invalid input" }, { status: 400 });
  }

  const db = await getDb();
  const [updated] = await db
    .update(variants)
    .set(parsed.data)
    .where(eq(variants.id, id))
    .returning();

  return NextResponse.json({ ok: true, variant: updated });
}

export async function DELETE(
  req: Request,
  { params }: { params: Promise<{ id: string }> }
) {
  const session = await getAdminSession();
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const { id } = await params;
  const db = await getDb();

  // Deactivate variant
  await db.update(variants).set({ active: false }).where(eq(variants.id, id));

  return NextResponse.json({ ok: true, message: "Variant deactivated." });
}
