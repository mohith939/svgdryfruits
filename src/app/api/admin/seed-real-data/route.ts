import { NextResponse } from "next/server";
import { getDb } from "@/db/client";
import { categories, products, shippingSettings, variants } from "@/db/schema";
import { eq, inArray } from "drizzle-orm";
import { REAL_CATEGORIES, REAL_PRODUCTS } from "../../../../../scripts/seed";

export async function POST() {
  try {
    const db = await getDb();

    // 1. Delete all demo products & variants
    const allProds = await db.select().from(products);
    const toDeleteIds: string[] = [];
    for (const p of allProds) {
      if (p.isDemo || p.slug.startsWith("demo-") || p.name.includes("(demo)")) {
        toDeleteIds.push(p.id);
      }
    }
    if (toDeleteIds.length > 0) {
      await db.delete(variants).where(inArray(variants.productId, toDeleteIds));
      await db.delete(products).where(inArray(products.id, toDeleteIds));
      console.log(`Deleted ${toDeleteIds.length} demo product(s).`);
    }

    // 2. Upsert categories
    const categoryMap = new Map<string, string>();
    for (const cat of REAL_CATEGORIES) {
      const [existing] = await db.select().from(categories).where(eq(categories.slug, cat.slug)).limit(1);
      if (existing) {
        await db.update(categories).set({
          name: cat.name,
          description: cat.description,
          sortOrder: cat.sortOrder,
          active: true,
        }).where(eq(categories.id, existing.id));
        categoryMap.set(cat.slug, existing.id);
      } else {
        const [inserted] = await db.insert(categories).values(cat).returning();
        categoryMap.set(cat.slug, inserted.id);
      }
    }

    // 3. Upsert real products and variants
    const synced = [];
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
          isCombo: p.isCombo,
          isDemo: false,
          active: true,
        }).where(eq(products.id, existing.id));
        prodId = existing.id;
      } else {
        const [inserted] = await db.insert(products).values({
          slug: p.slug,
          name: p.name,
          description: p.description,
          categoryId,
          imageUrl: p.imageUrl,
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
      synced.push(p.name);
    }

    // 4. Upsert shipping
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

    return NextResponse.json({ ok: true, deletedCount: toDeleteIds.length, syncedCount: synced.length, products: synced });
  } catch (err: any) {
    console.error("Seed real data error:", err);
    return NextResponse.json({ ok: false, error: err?.message || String(err) }, { status: 500 });
  }
}
