import { getDb } from "../src/db/client";
import { categories, comboItems, products, shippingSettings, variants } from "../src/db/schema";
import { eq, inArray } from "drizzle-orm";

export const REAL_CATEGORIES = [
  { slug: "nuts", name: "Nuts & Kernels", description: "Premium whole almonds, crunchy cashews, pistachios, and walnuts.", imageUrl: "/assets/cat-hd-nuts.png", sortOrder: 1 },
  { slug: "dried-fruits", name: "Dried Fruits", description: "Naturally sweet sun-dried dates, figs, apricots, and raisins.", imageUrl: "/assets/cat-hd-dried-fruits.png", sortOrder: 2 },
  { slug: "seeds", name: "Nutrient Seeds", description: "Nutrient-dense raw and roasted seeds packed with protein and zinc.", imageUrl: "/assets/cat-hd-seeds.png", sortOrder: 3 },
  { slug: "healthy-mix", name: "Healthy Mix", description: "Curated energy trail mixes and wholesome nutrient blends.", imageUrl: "/assets/cat-hd-healthy-mix.png", sortOrder: 4 },
  { slug: "gift-boxes", name: "Gift Boxes", description: "Luxury festive packaging for celebrations and corporate gifting.", imageUrl: "/assets/cat-hd-gift-boxes.png", sortOrder: 5 },
  { slug: "organic", name: "Organic Range", description: "100% certified organic and chemical-free dry fruits.", imageUrl: "/assets/cat-hd-organic.png", sortOrder: 6 },
];

export const REAL_PRODUCTS = [
  {
    slug: "pistachios",
    name: "Premium Pistachios",
    categorySlug: "nuts",
    description: "Naturally opened, gently slow-roasted jumbo California pistachios. Sourced from trusted growers for unbeatable freshness, rich crunch, and vibrant green kernels.",
    imageUrl: "/assets/shop-pistachios-hd.jpg",
    isCombo: false,
    variants: [
      { sku: "PIST-250", label: "250 g", pricePaise: 39900, comparePricePaise: 47900, stock: 45 },
      { sku: "PIST-500", label: "500 g", pricePaise: 76900, comparePricePaise: 92900, stock: 35 },
      { sku: "PIST-1KG", label: "1 kg", pricePaise: 149900, comparePricePaise: 179900, stock: 25 },
    ],
  },
  {
    slug: "almonds",
    name: "California Almonds",
    categorySlug: "nuts",
    description: "Naturally nutritious, crunchy and incredibly versatile — our California Almonds are sourced from trusted farms, carefully selected for their size, taste and quality.",
    imageUrl: "/assets/pdp-hero-almonds.jpg",
    isCombo: false,
    variants: [
      { sku: "ALM-250", label: "250 g", pricePaise: 34900, comparePricePaise: 39900, stock: 50 },
      { sku: "ALM-500", label: "500 g", pricePaise: 67900, comparePricePaise: 76900, stock: 40 },
      { sku: "ALM-1KG", label: "1 kg", pricePaise: 129900, comparePricePaise: 149900, stock: 30 },
    ],
  },
  {
    slug: "cashews",
    name: "Royal Cashews (Kaju)",
    categorySlug: "nuts",
    description: "Grade W240 jumbo whole white cashews. Crispy, naturally sweet, and buttery texture, sourced directly from coastal groves.",
    imageUrl: "/assets/shop-cashews.jpg",
    isCombo: false,
    variants: [
      { sku: "CAS-250", label: "250 g", pricePaise: 38900, comparePricePaise: 44900, stock: 40 },
      { sku: "CAS-500", label: "500 g", pricePaise: 74900, comparePricePaise: 87900, stock: 30 },
      { sku: "CAS-1KG", label: "1 kg", pricePaise: 144900, comparePricePaise: 169900, stock: 20 },
    ],
  },
  {
    slug: "walnuts",
    name: "Kashmiri Walnuts (Akhrot)",
    categorySlug: "nuts",
    description: "Premium extra-light Kashmiri walnut halves. Rich in brain-boosting Omega-3 fatty acids, vacuum packed for maximum crunch.",
    imageUrl: "/assets/shop-walnuts.jpg",
    isCombo: false,
    variants: [
      { sku: "WAL-250", label: "250 g", pricePaise: 42900, comparePricePaise: 49900, stock: 35 },
      { sku: "WAL-500", label: "500 g", pricePaise: 82900, comparePricePaise: 96900, stock: 25 },
      { sku: "WAL-1KG", label: "1 kg", pricePaise: 159900, comparePricePaise: 189900, stock: 15 },
    ],
  },
  {
    slug: "dates",
    name: "Royal Medjool Dates",
    categorySlug: "dried-fruits",
    description: "Succulent, soft, and naturally sweet Jumbo King Medjool dates with rich caramel notes and no added sugars.",
    imageUrl: "/assets/shop-premium-dates.jpg",
    isCombo: false,
    variants: [
      { sku: "DAT-250", label: "250 g", pricePaise: 29900, comparePricePaise: 34900, stock: 45 },
      { sku: "DAT-500", label: "500 g", pricePaise: 57900, comparePricePaise: 66900, stock: 30 },
      { sku: "DAT-1KG", label: "1 kg", pricePaise: 109900, comparePricePaise: 129900, stock: 20 },
    ],
  },
  {
    slug: "figs",
    name: "Dried Afghan Figs (Anjeer)",
    categorySlug: "dried-fruits",
    description: "Hand-threaded sun-dried organic Afghan figs with delightful chewiness, high in dietary fibre, calcium, and essential minerals.",
    imageUrl: "/assets/shop-dried-figs.jpg",
    isCombo: false,
    variants: [
      { sku: "FIG-250", label: "250 g", pricePaise: 44900, comparePricePaise: 52900, stock: 35 },
      { sku: "FIG-500", label: "500 g", pricePaise: 86900, comparePricePaise: 99900, stock: 25 },
      { sku: "FIG-1KG", label: "1 kg", pricePaise: 169900, comparePricePaise: 199900, stock: 15 },
    ],
  },
  {
    slug: "raisins",
    name: "Golden Long Raisins (Kishmish)",
    categorySlug: "dried-fruits",
    description: "Naturally shade-dried sweet long green Kishmish from Sangli orchards. Tender, plump, and packed with iron.",
    imageUrl: "/assets/shop-golden-raisins.jpg",
    isCombo: false,
    variants: [
      { sku: "RAI-250", label: "250 g", pricePaise: 18900, comparePricePaise: 22900, stock: 55 },
      { sku: "RAI-500", label: "500 g", pricePaise: 34900, comparePricePaise: 41900, stock: 40 },
      { sku: "RAI-1KG", label: "1 kg", pricePaise: 66900, comparePricePaise: 79900, stock: 30 },
    ],
  },
  {
    slug: "apricots",
    name: "Turkish Dried Apricots",
    categorySlug: "dried-fruits",
    description: "Authentic sun-ripened Malatya Turkish apricots. Beautiful golden color with luscious velvety texture and mild tartness.",
    imageUrl: "/assets/shop-dried-apricots.jpg",
    isCombo: false,
    variants: [
      { sku: "APR-250", label: "250 g", pricePaise: 32900, comparePricePaise: 38900, stock: 35 },
      { sku: "APR-500", label: "500 g", pricePaise: 62900, comparePricePaise: 73900, stock: 25 },
      { sku: "APR-1KG", label: "1 kg", pricePaise: 119900, comparePricePaise: 139900, stock: 20 },
    ],
  },
  {
    slug: "pumpkin-seeds",
    name: "Raw Pumpkin Seeds",
    categorySlug: "seeds",
    description: "AAA Grade unsalted shelled green pumpkin seeds. Rich in zinc, magnesium, and plant-based protein for health & fitness.",
    imageUrl: "/assets/shop-pumpkin-seeds.jpg",
    isCombo: false,
    variants: [
      { sku: "PMP-250", label: "250 g", pricePaise: 21900, comparePricePaise: 26900, stock: 45 },
      { sku: "PMP-500", label: "500 g", pricePaise: 39900, comparePricePaise: 49900, stock: 35 },
      { sku: "PMP-1KG", label: "1 kg", pricePaise: 74900, comparePricePaise: 89900, stock: 25 },
    ],
  },
  {
    slug: "sunflower-seeds",
    name: "Raw Sunflower Seeds",
    categorySlug: "seeds",
    description: "Gently cleaned whole sunflower seeds without shells. Packed with Vitamin E, selenium, and heart-healthy dietary fats.",
    imageUrl: "/assets/shop-sunflower-seeds.jpg",
    isCombo: false,
    variants: [
      { sku: "SUN-250", label: "250 g", pricePaise: 17900, comparePricePaise: 21900, stock: 40 },
      { sku: "SUN-500", label: "500 g", pricePaise: 32900, comparePricePaise: 39900, stock: 30 },
      { sku: "SUN-1KG", label: "1 kg", pricePaise: 59900, comparePricePaise: 72900, stock: 20 },
    ],
  },
  {
    slug: "healthy-mix",
    name: "Royal Daily Healthy Mix",
    categorySlug: "healthy-mix",
    description: "Supercharged energizing trail mix of roasted California almonds, crunchy cashews, pistachios, walnuts, cranberries, and pumpkin seeds.",
    imageUrl: "/assets/shop-mixed-nuts.jpg",
    isCombo: false,
    variants: [
      { sku: "MIX-250", label: "250 g", pricePaise: 36900, comparePricePaise: 42900, stock: 40 },
      { sku: "MIX-500", label: "500 g", pricePaise: 69900, comparePricePaise: 82900, stock: 30 },
      { sku: "MIX-1KG", label: "1 kg", pricePaise: 134900, comparePricePaise: 159900, stock: 20 },
    ],
  },
  {
    slug: "festive-gift-box",
    name: "Festive Dry Fruits Gift Box",
    categorySlug: "gift-boxes",
    description: "Luxury artisan wooden gift hamper featuring 4 individual air-sealed glass canisters of California Almonds, Royal Cashews, Pistachios, and Afghan Anjeer.",
    imageUrl: "/assets/mixed-fruit-reference.jpg",
    isCombo: true,
    variants: [
      { sku: "GIFT-1KG", label: "1 kg Deluxe Hamper", pricePaise: 189900, comparePricePaise: 229900, stock: 30 },
    ],
  },
  {
    slug: "organic-walnuts",
    name: "Organic Himalayan Walnuts",
    categorySlug: "organic",
    description: "Certified 100% organic handpicked wild walnuts harvested from pristine Himalayan orchards in Kashmir. Zero additives or preservatives.",
    imageUrl: "/assets/collection-walnuts.jpg",
    isCombo: false,
    variants: [
      { sku: "ORG-WAL-250", label: "250 g", pricePaise: 49900, comparePricePaise: 57900, stock: 25 },
      { sku: "ORG-WAL-500", label: "500 g", pricePaise: 94900, comparePricePaise: 109900, stock: 20 },
    ],
  },
];

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
  console.log("Upserted categories:", Array.from(categoryMap.keys()));

  // 3. Upsert products and variants
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
    console.log(`Synced product & variants: ${p.name} (${p.slug})`);
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

async function main() {
  await seedRealData();
  process.exit(0);
}

if (process.argv[1]?.endsWith("seed.ts")) {
  main().catch((err) => {
    console.error("Seeding error:", err);
    process.exit(1);
  });
}
