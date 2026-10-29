import { NextResponse } from "next/server";
import { asc, eq, inArray } from "drizzle-orm";
import { z } from "zod";
import { getDb } from "@/db/client";
import { categories, products, variants } from "@/db/schema";
import { getAdminSession } from "@/lib/auth";

const createProductSchema = z.object({
  name: z.string().trim().min(2),
  slug: z.string().trim().min(2).regex(/^[a-z0-9-]+$/, "Slug must only contain lowercase letters, numbers, and hyphens"),
  description: z.string().trim().optional(),
  categoryId: z.string().uuid().optional().nullable(),
  imageUrl: z.string().trim().optional().nullable(),
  images: z.array(z.string().trim()).optional().default([]),
  isCombo: z.boolean().default(false),
  active: z.boolean().default(true),
  variants: z.array(z.object({
    label: z.string().trim().min(1),
    sku: z.string().trim().min(1),
    pricePaise: z.number().int().min(0),
    comparePricePaise: z.number().int().min(0).optional().nullable(),
    stock: z.number().int().min(0).default(0),
    lowStockThreshold: z.number().int().min(0).default(5),
    active: z.boolean().default(true),
  })).optional(),
});

export async function GET() {
  const session = await getAdminSession();
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const db = await getDb();
  let rows = await db
    .select({
      product: products,
      category: categories,
    })
    .from(products)
    .leftJoin(categories, eq(products.categoryId, categories.id))
    .orderBy(asc(products.name));

  const productIds = rows.map((r) => r.product.id);
  const allVariants = productIds.length > 0
    ? await db
        .select()
        .from(variants)
        .where(inArray(variants.productId, productIds))
        .orderBy(asc(variants.pricePaise))
    : [];

  const variantsByProductId = new Map<string, typeof allVariants>();
  for (const v of allVariants) {
    const list = variantsByProductId.get(v.productId) ?? [];
    list.push(v);
    variantsByProductId.set(v.productId, list);
  }

  const list = rows.map((r) => ({
    ...r.product,
    category: r.category,
    variants: variantsByProductId.get(r.product.id) ?? [],
  }));

  return NextResponse.json({ products: list });
}

export async function POST(req: Request) {
  const session = await getAdminSession();
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const raw = await req.json().catch(() => null);
  const parsed = createProductSchema.safeParse(raw);
  if (!parsed.success) {
    return NextResponse.json({ error: parsed.error.issues[0]?.message || "Invalid product data" }, { status: 400 });
  }

  const { name, slug, description, categoryId, imageUrl, images, isCombo, active, variants: initVariants } = parsed.data;
  const db = await getDb();

  // Check unique slug
  const [existing] = await db.select().from(products).where(eq(products.slug, slug)).limit(1);
  if (existing) {
    return NextResponse.json({ error: "A product with this slug already exists." }, { status: 400 });
  }

  const primaryImage = imageUrl || (images && images.length > 0 ? images[0] : null);

  const [product] = await db.insert(products).values({
    name,
    slug,
    description: description || null,
    categoryId: categoryId || null,
    imageUrl: primaryImage,
    images: images || (primaryImage ? [primaryImage] : []),
    isCombo,
    active,
  }).returning();

  if (initVariants && initVariants.length > 0) {
    for (const v of initVariants) {
      await db.insert(variants).values({
        productId: product.id,
        sku: v.sku,
        label: v.label,
        pricePaise: v.pricePaise,
        comparePricePaise: v.comparePricePaise || null,
        stock: v.stock,
        lowStockThreshold: v.lowStockThreshold,
        active: v.active,
      });
    }
  }

  return NextResponse.json({ ok: true, product });
}
