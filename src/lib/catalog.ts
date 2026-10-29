import { and, asc, eq, ilike, inArray, or } from "drizzle-orm";
import { getDb } from "@/db/client";
import { categories, products, variants } from "@/db/schema";
import {
  REAL_CATEGORIES,
  REAL_CATEGORIES_BY_SLUG,
  REAL_PRODUCTS,
  REAL_PRODUCTS_BY_SLUG,
  type CatalogProduct,
} from "./catalog-data";

function toCatalogProductShape(p: CatalogProduct) {
  const cat = REAL_CATEGORIES_BY_SLUG.get(p.categorySlug) ?? null;
  return {
    id: p.id,
    categoryId: cat?.id ?? null,
    slug: p.slug,
    name: p.name,
    description: p.description,
    imageUrl: p.imageUrl,
    images: [] as string[],
    isCombo: p.isCombo,
    active: true,
    isDemo: false,
    createdAt: new Date(),
    category: cat
      ? {
          id: cat.id,
          slug: cat.slug,
          name: cat.name,
          description: cat.description,
          imageUrl: cat.imageUrl,
          sortOrder: cat.sortOrder,
          active: true,
          createdAt: new Date(),
        }
      : null,
    variants: p.variants.map((v) => ({
      id: v.id,
      productId: p.id,
      sku: v.sku,
      label: v.label,
      pricePaise: v.pricePaise,
      comparePricePaise: v.comparePricePaise,
      stock: v.stock,
      lowStockThreshold: 5,
      active: true,
    })),
  };
}

function getFallbackProducts(opts: { categorySlug?: string; q?: string; combosOnly?: boolean } = {}) {
  let list = REAL_PRODUCTS;
  if (opts.categorySlug) {
    list = list.filter((p) => p.categorySlug === opts.categorySlug);
  }
  if (opts.combosOnly) {
    list = list.filter((p) => p.isCombo);
  }
  if (opts.q) {
    const qLower = opts.q.toLowerCase();
    list = list.filter((p) => p.name.toLowerCase().includes(qLower) || p.description.toLowerCase().includes(qLower));
  }
  return list.map(toCatalogProductShape);
}

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

    if (!rows.length) {
      return getFallbackProducts(opts);
    }

    const productIds = rows.map((r) => r.product.id);
    const allVariants = productIds.length
      ? await db
          .select()
          .from(variants)
          .where(and(inArray(variants.productId, productIds), eq(variants.active, true)))
          .orderBy(asc(variants.pricePaise))
      : [];

    const variantsByProductId = new Map<string, typeof allVariants>();
    for (const v of allVariants) {
      const existing = variantsByProductId.get(v.productId) ?? [];
      existing.push(v);
      variantsByProductId.set(v.productId, existing);
    }

    return rows.map((r) => {
      const dbVs = variantsByProductId.get(r.product.id) ?? [];
      // If DB has product but no variants, fill from catalog
      const vs = dbVs.length
        ? dbVs
        : (REAL_PRODUCTS_BY_SLUG.get(r.product.slug)?.variants.map((v) => ({
            id: v.id,
            productId: r.product.id,
            sku: v.sku,
            label: v.label,
            pricePaise: v.pricePaise,
            comparePricePaise: v.comparePricePaise,
            stock: v.stock,
            lowStockThreshold: 5,
            active: true,
          })) ?? []);
      return { ...r.product, category: r.category, variants: vs };
    });
  } catch (err) {
    console.warn("listProducts query fallback:", err);
    return getFallbackProducts(opts);
  }
}

export async function getProduct(slug: string) {
  try {
    const db = await getDb();
    const [row] = await db
      .select({ product: products, category: categories })
      .from(products)
      .leftJoin(categories, eq(products.categoryId, categories.id))
      .where(eq(products.slug, slug))
      .limit(1);

    if (row && row.product) {
      const vs = await db
        .select()
        .from(variants)
        .where(and(eq(variants.productId, row.product.id), eq(variants.active, true)))
        .orderBy(asc(variants.pricePaise));

      const catalogFallback = REAL_PRODUCTS_BY_SLUG.get(slug);
      const finalVariants = vs.length
        ? vs
        : (catalogFallback?.variants.map((v) => ({
            id: v.id,
            productId: row.product.id,
            sku: v.sku,
            label: v.label,
            pricePaise: v.pricePaise,
            comparePricePaise: v.comparePricePaise,
            stock: v.stock,
            lowStockThreshold: 5,
            active: true,
          })) ?? []);

      return { ...row.product, category: row.category, variants: finalVariants };
    }

    return null;
  } catch (err) {
    console.warn("getProduct lookup error:", err);
    return null;
  }
}

export async function listCategories() {
  try {
    const db = await getDb();
    const rows = await db
      .select()
      .from(categories)
      .where(eq(categories.active, true))
      .orderBy(asc(categories.sortOrder));

    if (rows.length > 0) return rows;

    return REAL_CATEGORIES.map((c) => ({
      id: c.id,
      slug: c.slug,
      name: c.name,
      description: c.description,
      imageUrl: c.imageUrl,
      sortOrder: c.sortOrder,
      active: true,
      createdAt: new Date(),
    }));
  } catch (err) {
    console.warn("listCategories query error:", err);
    return REAL_CATEGORIES.map((c) => ({
      id: c.id,
      slug: c.slug,
      name: c.name,
      description: c.description,
      imageUrl: c.imageUrl,
      sortOrder: c.sortOrder,
      active: true,
      createdAt: new Date(),
    }));
  }
}
