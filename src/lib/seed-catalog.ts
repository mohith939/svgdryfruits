import { getDb } from "@/db/client";
import { categories, products, shippingSettings, variants } from "@/db/schema";
import { eq, inArray } from "drizzle-orm";
import { REAL_CATEGORIES, REAL_PRODUCTS } from "./catalog-data";

export { REAL_CATEGORIES, REAL_PRODUCTS };

export async function seedRealData() {
  const db = await getDb();

  // 1. Delete all demo products & their variants
  const demoProducts = await db.select().from(products).where(eq(products.isDemo, true));
  if (demoProducts.length > 0) {
    const demoIds = demoProducts.map((p) => p.id);
    await db.delete(variants).where(inArray(variants.productId, demoIds));
    await db.delete(products).where(inArray(products.id, demoIds));
    console.log(`Deleted ${demoProducts.length} demo product(s).`);
  }

  // Also remove any stray products with "demo-" in slug
  const strayDemo = await db.select().from(products);
  for (const p of strayDemo) {
    if (p.slug.startsWith("demo-") || p.name.includes("(demo)")) {
      await db.delete(variants).where(eq(variants.productId, p.id));
      await db.delete(products).where(eq(products.id, p.id));
      console.log(`Removed stray demo product: ${p.slug}`);
    }
  }

  // 2. Upsert categories with stable IDs
  const categoryMap = new Map<string, string>();
  for (const cat of REAL_CATEGORIES) {
    const [existing] = await db.select().from(categories).where(eq(categories.slug, cat.slug)).limit(1);
    if (existing) {
      await db.update(categories).set({
        name: cat.name,
        description: cat.description,
        sortOrder: cat.sortOrder,
        imageUrl: cat.imageUrl,
        active: true,
      }).where(eq(categories.id, existing.id));
      categoryMap.set(cat.slug, existing.id);
    } else {
      const [inserted] = await db.insert(categories).values({
        id: cat.id,
        slug: cat.slug,
        name: cat.name,
        description: cat.description,
        imageUrl: cat.imageUrl,
        sortOrder: cat.sortOrder,
        active: true,
      }).returning();
      categoryMap.set(cat.slug, inserted.id);
    }
  }

  // 3. Upsert products and variants with stable IDs
  for (const p of REAL_PRODUCTS) {
    const categoryId = categoryMap.get(p.categorySlug) || null;
    let prodId: string;

    const [existing] = await db.select().from(products).where(eq(products.slug, p.slug)).limit(1);
    if (existing) {
      await db.update(products).set({
        name: p.name,
        description: p.description,
        categoryId,
        imageUrl: p.imageUrl,
        images: [p.imageUrl],
        isCombo: p.isCombo,
        isDemo: false,
        active: true,
      }).where(eq(products.id, existing.id));
      prodId = existing.id;
    } else {
      const [inserted] = await db.insert(products).values({
        id: p.id,
        slug: p.slug,
        name: p.name,
        description: p.description,
        categoryId,
        imageUrl: p.imageUrl,
        images: [p.imageUrl],
        isCombo: p.isCombo,
        isDemo: false,
        active: true,
      }).returning();
      prodId = inserted.id;
    }

    // Upsert variants
    for (const v of p.variants) {
      const [existingVar] = await db.select().from(variants).where(eq(variants.sku, v.sku)).limit(1);
      if (existingVar) {
        await db.update(variants).set({
          productId: prodId,
          label: v.label,
          pricePaise: v.pricePaise,
          comparePricePaise: v.comparePricePaise,
          stock: v.stock,
          active: true,
        }).where(eq(variants.id, existingVar.id));
      } else {
        await db.insert(variants).values({
          id: v.id,
          productId: prodId,
          sku: v.sku,
          label: v.label,
          pricePaise: v.pricePaise,
          comparePricePaise: v.comparePricePaise,
          stock: v.stock,
          active: true,
        });
      }
    }
  }

  // 4. Upsert shipping settings
  await db.insert(shippingSettings).values({
    id: 1,
    flatChargePaise: 9900,
    freeThresholdPaise: 99900,
    active: true,
  }).onConflictDoUpdate({
    target: shippingSettings.id,
    set: {
      flatChargePaise: 9900,
      freeThresholdPaise: 99900,
      active: true,
    },
  });

  console.log("Real data seeding complete!");
}
