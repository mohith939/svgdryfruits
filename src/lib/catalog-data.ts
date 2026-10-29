export interface CatalogCategory {
  id: string;
  slug: string;
  name: string;
  description: string;
  imageUrl: string;
  sortOrder: number;
}

export interface CatalogVariant {
  id: string;
  sku: string;
  label: string;
  pricePaise: number;
  comparePricePaise: number;
  stock: number;
}

export interface CatalogProduct {
  id: string;
  slug: string;
  name: string;
  categorySlug: string;
  description: string;
  imageUrl: string;
  isCombo: boolean;
  variants: CatalogVariant[];
}

export const REAL_CATEGORIES: CatalogCategory[] = [
  {
    id: "a0000001-0000-4000-8000-000000000001",
    slug: "nuts",
    name: "Nuts & Kernels",
    description: "Premium whole almonds, crunchy cashews, pistachios, and walnuts.",
    imageUrl: "/assets/cat-hd-nuts.png",
    sortOrder: 1,
  },
  {
    id: "a0000001-0000-4000-8000-000000000002",
    slug: "dried-fruits",
    name: "Dried Fruits",
    description: "Naturally sweet sun-dried dates, figs, apricots, and raisins.",
    imageUrl: "/assets/cat-hd-dried-fruits.png",
    sortOrder: 2,
  },
  {
    id: "a0000001-0000-4000-8000-000000000003",
    slug: "seeds",
    name: "Nutrient Seeds",
    description: "Nutrient-dense raw and roasted seeds packed with protein and zinc.",
    imageUrl: "/assets/cat-hd-seeds.png",
    sortOrder: 3,
  },
  {
    id: "a0000001-0000-4000-8000-000000000004",
    slug: "healthy-mix",
    name: "Healthy Mix",
    description: "Curated energy trail mixes and wholesome nutrient blends.",
    imageUrl: "/assets/cat-hd-healthy-mix.png",
    sortOrder: 4,
  },
  {
    id: "a0000001-0000-4000-8000-000000000005",
    slug: "combos",
    name: "Combos",
    description: "Curated celebration packs, mixed hampers, and value bundles.",
    imageUrl: "/assets/cat-hd-gift-boxes.png",
    sortOrder: 5,
  },
];

export const REAL_PRODUCTS: CatalogProduct[] = [
  {
    id: "b0000001-0000-4000-8000-000000000001",
    slug: "pistachios",
    name: "Premium Pistachios",
    categorySlug: "nuts",
    description: "Naturally opened, gently slow-roasted jumbo California pistachios. Sourced from trusted growers for unbeatable freshness, rich crunch, and vibrant green kernels.",
    imageUrl: "/assets/shop-pistachios-hd.jpg",
    isCombo: false,
    variants: [
      { id: "c0000001-0001-4000-8000-000000000001", sku: "PIST-250", label: "250 g", pricePaise: 39900, comparePricePaise: 47900, stock: 45 },
      { id: "c0000001-0002-4000-8000-000000000001", sku: "PIST-500", label: "500 g", pricePaise: 76900, comparePricePaise: 92900, stock: 35 },
      { id: "c0000001-0003-4000-8000-000000000001", sku: "PIST-1KG", label: "1 kg", pricePaise: 149900, comparePricePaise: 179900, stock: 25 },
    ],
  },
  {
    id: "b0000001-0000-4000-8000-000000000002",
    slug: "almonds",
    name: "California Almonds",
    categorySlug: "nuts",
    description: "Naturally nutritious, crunchy and incredibly versatile — our California Almonds are sourced from trusted farms, carefully selected for their size, taste and quality.",
    imageUrl: "/assets/pdp-hero-almonds.jpg",
    isCombo: false,
    variants: [
      { id: "c0000001-0001-4000-8000-000000000002", sku: "ALM-250", label: "250 g", pricePaise: 34900, comparePricePaise: 39900, stock: 50 },
      { id: "c0000001-0002-4000-8000-000000000002", sku: "ALM-500", label: "500 g", pricePaise: 67900, comparePricePaise: 76900, stock: 40 },
      { id: "c0000001-0003-4000-8000-000000000002", sku: "ALM-1KG", label: "1 kg", pricePaise: 129900, comparePricePaise: 149900, stock: 30 },
    ],
  },
  {
    id: "b0000001-0000-4000-8000-000000000003",
    slug: "cashews",
    name: "Royal Cashews (Kaju)",
    categorySlug: "nuts",
    description: "Grade W240 jumbo whole white cashews. Crispy, naturally sweet, and buttery texture, sourced directly from coastal groves.",
    imageUrl: "/assets/shop-cashews.jpg",
    isCombo: false,
    variants: [
      { id: "c0000001-0001-4000-8000-000000000003", sku: "CAS-250", label: "250 g", pricePaise: 38900, comparePricePaise: 44900, stock: 40 },
      { id: "c0000001-0002-4000-8000-000000000003", sku: "CAS-500", label: "500 g", pricePaise: 74900, comparePricePaise: 87900, stock: 30 },
      { id: "c0000001-0003-4000-8000-000000000003", sku: "CAS-1KG", label: "1 kg", pricePaise: 144900, comparePricePaise: 169900, stock: 20 },
    ],
  },
  {
    id: "b0000001-0000-4000-8000-000000000004",
    slug: "walnuts",
    name: "Kashmiri Walnuts (Akhrot)",
    categorySlug: "nuts",
    description: "Premium extra-light Kashmiri walnut halves. Rich in brain-boosting Omega-3 fatty acids, vacuum packed for maximum crunch.",
    imageUrl: "/assets/shop-walnuts.jpg",
    isCombo: false,
    variants: [
      { id: "c0000001-0001-4000-8000-000000000004", sku: "WAL-250", label: "250 g", pricePaise: 42900, comparePricePaise: 49900, stock: 35 },
      { id: "c0000001-0002-4000-8000-000000000004", sku: "WAL-500", label: "500 g", pricePaise: 82900, comparePricePaise: 96900, stock: 25 },
      { id: "c0000001-0003-4000-8000-000000000004", sku: "WAL-1KG", label: "1 kg", pricePaise: 159900, comparePricePaise: 189900, stock: 15 },
    ],
  },
  {
    id: "b0000001-0000-4000-8000-000000000005",
    slug: "dates",
    name: "Royal Medjool Dates",
    categorySlug: "dried-fruits",
    description: "Succulent, soft, and naturally sweet Jumbo King Medjool dates with rich caramel notes and no added sugars.",
    imageUrl: "/assets/shop-premium-dates.jpg",
    isCombo: false,
    variants: [
      { id: "c0000001-0001-4000-8000-000000000005", sku: "DAT-250", label: "250 g", pricePaise: 29900, comparePricePaise: 34900, stock: 45 },
      { id: "c0000001-0002-4000-8000-000000000005", sku: "DAT-500", label: "500 g", pricePaise: 57900, comparePricePaise: 66900, stock: 30 },
      { id: "c0000001-0003-4000-8000-000000000005", sku: "DAT-1KG", label: "1 kg", pricePaise: 109900, comparePricePaise: 129900, stock: 20 },
    ],
  },
  {
    id: "b0000001-0000-4000-8000-000000000006",
    slug: "figs",
    name: "Dried Afghan Figs (Anjeer)",
    categorySlug: "dried-fruits",
    description: "Hand-threaded sun-dried organic Afghan figs with delightful chewiness, high in dietary fibre, calcium, and essential minerals.",
    imageUrl: "/assets/shop-dried-figs.jpg",
    isCombo: false,
    variants: [
      { id: "c0000001-0001-4000-8000-000000000006", sku: "FIG-250", label: "250 g", pricePaise: 44900, comparePricePaise: 52900, stock: 35 },
      { id: "c0000001-0002-4000-8000-000000000006", sku: "FIG-500", label: "500 g", pricePaise: 86900, comparePricePaise: 99900, stock: 25 },
      { id: "c0000001-0003-4000-8000-000000000006", sku: "FIG-1KG", label: "1 kg", pricePaise: 169900, comparePricePaise: 199900, stock: 15 },
    ],
  },
  {
    id: "b0000001-0000-4000-8000-000000000007",
    slug: "raisins",
    name: "Golden Long Raisins (Kishmish)",
    categorySlug: "dried-fruits",
    description: "Naturally shade-dried sweet long green Kishmish from Sangli orchards. Tender, plump, and packed with iron.",
    imageUrl: "/assets/shop-golden-raisins.jpg",
    isCombo: false,
    variants: [
      { id: "c0000001-0001-4000-8000-000000000007", sku: "RAI-250", label: "250 g", pricePaise: 18900, comparePricePaise: 22900, stock: 55 },
      { id: "c0000001-0002-4000-8000-000000000007", sku: "RAI-500", label: "500 g", pricePaise: 34900, comparePricePaise: 41900, stock: 40 },
      { id: "c0000001-0003-4000-8000-000000000007", sku: "RAI-1KG", label: "1 kg", pricePaise: 66900, comparePricePaise: 79900, stock: 30 },
    ],
  },
  {
    id: "b0000001-0000-4000-8000-000000000008",
    slug: "apricots",
    name: "Turkish Dried Apricots",
    categorySlug: "dried-fruits",
    description: "Authentic sun-ripened Malatya Turkish apricots. Beautiful golden color with luscious velvety texture and mild tartness.",
    imageUrl: "/assets/shop-dried-apricots.jpg",
    isCombo: false,
    variants: [
      { id: "c0000001-0001-4000-8000-000000000008", sku: "APR-250", label: "250 g", pricePaise: 32900, comparePricePaise: 38900, stock: 35 },
      { id: "c0000001-0002-4000-8000-000000000008", sku: "APR-500", label: "500 g", pricePaise: 62900, comparePricePaise: 73900, stock: 25 },
      { id: "c0000001-0003-4000-8000-000000000008", sku: "APR-1KG", label: "1 kg", pricePaise: 119900, comparePricePaise: 139900, stock: 20 },
    ],
  },
  {
    id: "b0000001-0000-4000-8000-000000000009",
    slug: "pumpkin-seeds",
    name: "Raw Pumpkin Seeds",
    categorySlug: "seeds",
    description: "AAA Grade unsalted shelled green pumpkin seeds. Rich in zinc, magnesium, and plant-based protein for health & fitness.",
    imageUrl: "/assets/shop-pumpkin-seeds.jpg",
    isCombo: false,
    variants: [
      { id: "c0000001-0001-4000-8000-000000000009", sku: "PMP-250", label: "250 g", pricePaise: 21900, comparePricePaise: 26900, stock: 45 },
      { id: "c0000001-0002-4000-8000-000000000009", sku: "PMP-500", label: "500 g", pricePaise: 39900, comparePricePaise: 49900, stock: 35 },
      { id: "c0000001-0003-4000-8000-000000000009", sku: "PMP-1KG", label: "1 kg", pricePaise: 74900, comparePricePaise: 89900, stock: 25 },
    ],
  },
  {
    id: "b0000001-0000-4000-8000-000000000010",
    slug: "sunflower-seeds",
    name: "Raw Sunflower Seeds",
    categorySlug: "seeds",
    description: "Gently cleaned whole sunflower seeds without shells. Packed with Vitamin E, selenium, and heart-healthy dietary fats.",
    imageUrl: "/assets/shop-sunflower-seeds.jpg",
    isCombo: false,
    variants: [
      { id: "c0000001-0001-4000-8000-000000000010", sku: "SUN-250", label: "250 g", pricePaise: 17900, comparePricePaise: 21900, stock: 40 },
      { id: "c0000001-0002-4000-8000-000000000010", sku: "SUN-500", label: "500 g", pricePaise: 32900, comparePricePaise: 39900, stock: 30 },
      { id: "c0000001-0003-4000-8000-000000000010", sku: "SUN-1KG", label: "1 kg", pricePaise: 59900, comparePricePaise: 72900, stock: 20 },
    ],
  },
  {
    id: "b0000001-0000-4000-8000-000000000011",
    slug: "healthy-mix",
    name: "Royal Daily Healthy Mix",
    categorySlug: "healthy-mix",
    description: "Supercharged energizing trail mix of roasted California almonds, crunchy cashews, pistachios, walnuts, cranberries, and pumpkin seeds.",
    imageUrl: "/assets/shop-mixed-nuts.jpg",
    isCombo: false,
    variants: [
      { id: "c0000001-0001-4000-8000-000000000011", sku: "MIX-250", label: "250 g", pricePaise: 36900, comparePricePaise: 42900, stock: 40 },
      { id: "c0000001-0002-4000-8000-000000000011", sku: "MIX-500", label: "500 g", pricePaise: 69900, comparePricePaise: 82900, stock: 30 },
      { id: "c0000001-0003-4000-8000-000000000011", sku: "MIX-1KG", label: "1 kg", pricePaise: 134900, comparePricePaise: 159900, stock: 20 },
    ],
  },
  {
    id: "b0000001-0000-4000-8000-000000000012",
    slug: "festive-gift-box",
    name: "Festive Dry Fruits Gift Box",
    categorySlug: "combos",
    description: "Luxury artisan wooden gift hamper featuring 4 individual air-sealed glass canisters of California Almonds, Royal Cashews, Pistachios, and Afghan Anjeer.",
    imageUrl: "/assets/mixed-fruit-reference.jpg",
    isCombo: true,
    variants: [
      { id: "c0000001-0001-4000-8000-000000000012", sku: "GIFT-1KG", label: "1 kg Deluxe Hamper", pricePaise: 189900, comparePricePaise: 229900, stock: 30 },
    ],
  },
  {
    id: "b0000001-0000-4000-8000-000000000013",
    slug: "organic-walnuts",
    name: "Organic Himalayan Walnuts",
    categorySlug: "nuts",
    description: "Certified 100% organic handpicked wild walnuts harvested from pristine Himalayan orchards in Kashmir. Zero additives or preservatives.",
    imageUrl: "/assets/collection-walnuts.jpg",
    isCombo: false,
    variants: [
      { id: "c0000001-0001-4000-8000-000000000013", sku: "ORG-WAL-250", label: "250 g", pricePaise: 49900, comparePricePaise: 57900, stock: 25 },
      { id: "c0000001-0002-4000-8000-000000000013", sku: "ORG-WAL-500", label: "500 g", pricePaise: 94900, comparePricePaise: 109900, stock: 20 },
    ],
  },
];

export const REAL_CATEGORIES_BY_SLUG = new Map<string, CatalogCategory>(
  REAL_CATEGORIES.map((c) => [c.slug, c])
);

export const REAL_PRODUCTS_BY_SLUG = new Map<string, CatalogProduct>(
  REAL_PRODUCTS.map((p) => [p.slug, p])
);

export const REAL_VARIANTS_BY_ID = new Map<
  string,
  { product: CatalogProduct; variant: CatalogVariant }
>();

export const REAL_VARIANTS_BY_SKU = new Map<
  string,
  { product: CatalogProduct; variant: CatalogVariant }
>();

for (const product of REAL_PRODUCTS) {
  for (const variant of product.variants) {
    REAL_VARIANTS_BY_ID.set(variant.id, { product, variant });
    REAL_VARIANTS_BY_SKU.set(variant.sku.toUpperCase(), { product, variant });
  }
}

export function findCatalogProduct(slug: string): CatalogProduct | null {
  return REAL_PRODUCTS_BY_SLUG.get(slug) ?? null;
}

export function findCatalogVariant(idOrSku: string): { product: CatalogProduct; variant: CatalogVariant } | null {
  if (REAL_VARIANTS_BY_ID.has(idOrSku)) {
    return REAL_VARIANTS_BY_ID.get(idOrSku)!;
  }
  const upper = idOrSku.toUpperCase();
  if (REAL_VARIANTS_BY_SKU.has(upper)) {
    return REAL_VARIANTS_BY_SKU.get(upper)!;
  }
  return null;
}
