import { NextResponse } from "next/server";
import { asc, eq } from "drizzle-orm";
import { z } from "zod";
import { getDb } from "@/db/client";
import { categories, products, variants } from "@/db/schema";
import { getAdminSession } from "@/lib/auth";

const updateProductSchema = z.object({
  name: z.string().trim().min(2).optional(),
  slug: z.string().trim().min(2).regex(/^[a-z0-9-]+$/).optional(),
  description: z.string().trim().optional().nullable(),
  categoryId: z.string().uuid().optional().nullable(),
  imageUrl: z.string().trim().optional().nullable(),
  images: z.array(z.string().trim()).optional(),
  isCombo: z.boolean().optional(),
  active: z.boolean().optional(),
});

export async function GET(
  req: Request,
  { params }: { params: Promise<{ id: string }> }
) {
  const session = await getAdminSession();
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const { id } = await params;
  const db = await getDb();

  const [row] = await db
    .select({ product: products, category: categories })
    .from(products)
    .leftJoin(categories, eq(products.categoryId, categories.id))
    .where(eq(products.id, id))
    .limit(1);

  if (!row) return NextResponse.json({ error: "Product not found" }, { status: 404 });

  const vs = await db
    .select()
    .from(variants)
    .where(eq(variants.productId, id))
    .orderBy(asc(variants.pricePaise));

  return NextResponse.json({ product: { ...row.product, category: row.category, variants: vs } });
}

export async function PUT(
  req: Request,
  { params }: { params: Promise<{ id: string }> }
) {
  const session = await getAdminSession();
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const { id } = await params;
  const raw = await req.json().catch(() => null);
  const parsed = updateProductSchema.safeParse(raw);

  if (!parsed.success) {
    return NextResponse.json({ error: parsed.error.issues[0]?.message || "Invalid input" }, { status: 400 });
  }

  const db = await getDb();
  const [updated] = await db
    .update(products)
    .set(parsed.data)
    .where(eq(products.id, id))
    .returning();

  return NextResponse.json({ ok: true, product: updated });
}

export async function DELETE(
  req: Request,
  { params }: { params: Promise<{ id: string }> }
) {
  const session = await getAdminSession();
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const { id } = await params;
  const db = await getDb();

  try {
    const { orderItems, reservations, comboItems } = await import("@/db/schema");
    const { inArray } = await import("drizzle-orm");
    const vs = await db.select().from(variants).where(eq(variants.productId, id));
    const variantIds = vs.map((v) => v.id);

    if (variantIds.length > 0) {
      const { inventoryMovements } = await import("@/db/schema");
      try {
        await db.delete(reservations).where(inArray(reservations.variantId, variantIds));
      } catch {}
      try {
        await db.delete(comboItems).where(inArray(comboItems.componentVariantId, variantIds));
      } catch {}
      try {
        await db.delete(inventoryMovements).where(inArray(inventoryMovements.variantId, variantIds));
      } catch {}
      try {
        await db.update(orderItems).set({ variantId: null }).where(inArray(orderItems.variantId, variantIds));
      } catch {}
      await db.delete(variants).where(inArray(variants.id, variantIds));
    }

    // 5. Delete product permanently
    await db.delete(products).where(eq(products.id, id));

    return NextResponse.json({ ok: true, deleted: true, message: "Product deleted permanently." });
  } catch (err: any) {
    console.error("Delete product error:", err);
    return NextResponse.json({ error: err?.message || "Failed to delete product" }, { status: 500 });
  }
}
