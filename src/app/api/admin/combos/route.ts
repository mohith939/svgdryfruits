import { NextResponse } from "next/server";
import { eq } from "drizzle-orm";
import { z } from "zod";
import { getDb } from "@/db/client";
import { comboItems, products, variants } from "@/db/schema";
import { getAdminSession } from "@/lib/auth";

const addComboItemSchema = z.object({
  comboVariantId: z.string().uuid(),
  componentVariantId: z.string().uuid(),
  quantity: z.number().int().min(1).default(1),
});

export async function GET() {
  const session = await getAdminSession();
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const db = await getDb();

  // Find all combo products
  const comboProducts = await db
    .select()
    .from(products)
    .where(eq(products.isCombo, true));

  const list = [];
  for (const cp of comboProducts) {
    const cpVariants = await db
      .select()
      .from(variants)
      .where(eq(variants.productId, cp.id));

    const allItems = [];
    for (const v of cpVariants) {
      const items = await db
        .select({
          id: comboItems.id,
          quantity: comboItems.quantity,
          componentVariantId: comboItems.componentVariantId,
          componentSku: variants.sku,
          componentLabel: variants.label,
          componentStock: variants.stock,
          componentProductName: products.name,
        })
        .from(comboItems)
        .innerJoin(variants, eq(comboItems.componentVariantId, variants.id))
        .innerJoin(products, eq(variants.productId, products.id))
        .where(eq(comboItems.comboVariantId, v.id));

      allItems.push({ variant: v, components: items });
    }

    list.push({ product: cp, variants: allItems });
  }

  // Also return all single variants so admin can pick components to bundle
  const singleProducts = await db
    .select({ variant: variants, productName: products.name })
    .from(variants)
    .innerJoin(products, eq(variants.productId, products.id))
    .where(eq(products.isCombo, false));

  return NextResponse.json({ combos: list, availableComponents: singleProducts });
}

export async function POST(req: Request) {
  const session = await getAdminSession();
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const raw = await req.json().catch(() => null);
  const parsed = addComboItemSchema.safeParse(raw);

  if (!parsed.success) {
    return NextResponse.json({ error: "Invalid combo item data" }, { status: 400 });
  }

  const db = await getDb();
  const [created] = await db.insert(comboItems).values(parsed.data).returning();

  return NextResponse.json({ ok: true, item: created });
}

export async function DELETE(req: Request) {
  const session = await getAdminSession();
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const url = new URL(req.url);
  const id = url.searchParams.get("id");
  if (!id) return NextResponse.json({ error: "Missing ID" }, { status: 400 });

  const db = await getDb();
  await db.delete(comboItems).where(eq(comboItems.id, id));

  return NextResponse.json({ ok: true });
}
