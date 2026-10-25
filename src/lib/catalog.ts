import { and, asc, eq, ilike, or } from "drizzle-orm";
import { getDb } from "@/db/client";
import { categories, products, variants } from "@/db/schema";

export async function listProducts(opts: { categorySlug?: string; q?: string; combosOnly?: boolean } = {}) {
  try {
    const db = await getDb();
    const conds = [eq(products.active, true)];
    if (opts.categorySlug) conds.push(eq(categories.slug, opts.categorySlug));
    if (opts.combosOnly) conds.push(eq(products.isCombo, true));
    if (opts.q) {
      const like = `%${opts.q.replace(/[%_\\]/g, "\\$&")}%`;
      conds.push(or(ilike(products.name, like), ilike(products.description, like))!);
    }
    const rows = await db
      .select({ product: products, category: categories })
      .from(products)
      .leftJoin(categories, eq(products.categoryId, categories.id))
      .where(and(...conds))
      .orderBy(asc(products.name));
    const out = [];
    for (const r of rows) {
      const vs = await db.select().from(variants).where(and(eq(variants.productId, r.product.id), eq(variants.active, true))).orderBy(asc(variants.pricePaise));
      out.push({ ...r.product, category: r.category, variants: vs });
    }
    return out;
  } catch (err) {
    console.warn("listProducts query fallback:", err);
    return [];
  }
}

export async function getProduct(slug: string) {
  try {
    const prods = await listProducts();
    return prods.find((p) => p.slug === slug) ?? null;
  } catch (err) {
    console.warn("getProduct lookup error:", err);
    return null;
  }
}

export async function listCategories() {
  try {
    const db = await getDb();
    return await db.select().from(categories).where(eq(categories.active, true)).orderBy(asc(categories.sortOrder));
  } catch (err) {
    console.warn("listCategories query fallback:", err);
    return [];
  }
}
