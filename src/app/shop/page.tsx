import type { Metadata } from "next";
import { listCategories, listProducts } from "@/lib/catalog";
import { ShopCatalog, type ShopProduct } from "@/components/ShopCatalog";

export const dynamic = "force-dynamic";
export const metadata: Metadata = {
  title: "Explore Our Collection — SVG Dry Fruits",
  description: "Wholesome nuts, dried fruits and natural foods, carefully sourced from trusted growers.",
};

const DEFAULT_CATEGORIES = [
  { id: "nuts", slug: "nuts", name: "Nuts & Kernels", imageUrl: "/assets/cat-hd-nuts.png" },
  { id: "dried-fruits", slug: "dried-fruits", name: "Dried Fruits", imageUrl: "/assets/cat-hd-dried-fruits.png" },
  { id: "seeds", slug: "seeds", name: "Nutrient Seeds", imageUrl: "/assets/cat-hd-seeds.png" },
  { id: "healthy-mix", slug: "healthy-mix", name: "Healthy Mix", imageUrl: "/assets/cat-hd-healthy-mix.png" },
  { id: "gift-boxes", slug: "gift-boxes", name: "Gift Boxes", imageUrl: "/assets/cat-hd-gift-boxes.png" },
  { id: "organic", slug: "organic", name: "Organic Range", imageUrl: "/assets/cat-hd-organic.png" },
];

const DEFAULT_PRODUCTS: ShopProduct[] = [
  {
    id: "almonds-id",
    slug: "almonds",
    name: "California Almonds",
    categorySlug: "nuts",
    description: "Naturally nutritious, crunchy and versatile California Almonds.",
    imageUrl: "/assets/pdp-hero-almonds.jpg",
    variants: [
      { id: "alm-250", sku: "ALM-250", label: "250 g", pricePaise: 34900, comparePricePaise: 39900, stock: 50 },
      { id: "alm-500", sku: "ALM-500", label: "500 g", pricePaise: 67900, comparePricePaise: 76900, stock: 40 },
      { id: "alm-1kg", sku: "ALM-1KG", label: "1 kg", pricePaise: 129900, comparePricePaise: 149900, stock: 30 },
    ],
  },
  {
    id: "cashews-id",
    slug: "cashews",
    name: "Royal Cashews (Kaju)",
    categorySlug: "nuts",
    description: "Grade W240 jumbo whole white cashews.",
    imageUrl: "/assets/shop-cashews.jpg",
    variants: [
      { id: "cas-250", sku: "CAS-250", label: "250 g", pricePaise: 38900, comparePricePaise: 44900, stock: 40 },
      { id: "cas-500", sku: "CAS-500", label: "500 g", pricePaise: 74900, comparePricePaise: 87900, stock: 30 },
    ],
  },
  {
    id: "walnuts-id",
    slug: "walnuts",
    name: "Kashmiri Walnuts (Akhrot)",
    categorySlug: "nuts",
    description: "Premium extra-light Kashmiri walnut halves.",
    imageUrl: "/assets/shop-walnuts.jpg",
    variants: [
      { id: "wal-250", sku: "WAL-250", label: "250 g", pricePaise: 42900, comparePricePaise: 49900, stock: 35 },
      { id: "wal-500", sku: "WAL-500", label: "500 g", pricePaise: 82900, comparePricePaise: 96900, stock: 25 },
    ],
  },
  {
    id: "dates-id",
    slug: "dates",
    name: "Royal Medjool Dates",
    categorySlug: "dried-fruits",
    description: "Succulent, soft, and naturally sweet Jumbo King Medjool dates.",
    imageUrl: "/assets/shop-premium-dates.jpg",
    variants: [
      { id: "dat-250", sku: "DAT-250", label: "250 g", pricePaise: 29900, comparePricePaise: 34900, stock: 45 },
      { id: "dat-500", sku: "DAT-500", label: "500 g", pricePaise: 57900, comparePricePaise: 66900, stock: 30 },
    ],
  },
  {
    id: "figs-id",
    slug: "figs",
    name: "Dried Afghan Figs (Anjeer)",
    categorySlug: "dried-fruits",
    description: "Hand-threaded sun-dried organic Afghan figs.",
    imageUrl: "/assets/shop-dried-figs.jpg",
    variants: [
      { id: "fig-250", sku: "FIG-250", label: "250 g", pricePaise: 44900, comparePricePaise: 52900, stock: 35 },
      { id: "fig-500", sku: "FIG-500", label: "500 g", pricePaise: 86900, comparePricePaise: 99900, stock: 25 },
    ],
  },
  {
    id: "raisins-id",
    slug: "raisins",
    name: "Golden Long Raisins (Kishmish)",
    categorySlug: "dried-fruits",
    description: "Naturally shade-dried sweet long green Kishmish from Sangli.",
    imageUrl: "/assets/shop-golden-raisins.jpg",
    variants: [
      { id: "rai-250", sku: "RAI-250", label: "250 g", pricePaise: 18900, comparePricePaise: 22900, stock: 55 },
      { id: "rai-500", sku: "RAI-500", label: "500 g", pricePaise: 34900, comparePricePaise: 41900, stock: 40 },
    ],
  },
  {
    id: "apricots-id",
    slug: "apricots",
    name: "Turkish Dried Apricots",
    categorySlug: "dried-fruits",
    description: "Authentic sun-ripened Malatya Turkish apricots.",
    imageUrl: "/assets/shop-dried-apricots.jpg",
    variants: [
      { id: "apr-250", sku: "APR-250", label: "250 g", pricePaise: 32900, comparePricePaise: 38900, stock: 35 },
      { id: "apr-500", sku: "APR-500", label: "500 g", pricePaise: 62900, comparePricePaise: 73900, stock: 25 },
    ],
  },
  {
    id: "pumpkin-seeds-id",
    slug: "pumpkin-seeds",
    name: "Raw Pumpkin Seeds",
    categorySlug: "seeds",
    description: "AAA Grade unsalted shelled green pumpkin seeds.",
    imageUrl: "/assets/shop-pumpkin-seeds.jpg",
    variants: [
      { id: "pmp-250", sku: "PMP-250", label: "250 g", pricePaise: 21900, comparePricePaise: 26900, stock: 45 },
      { id: "pmp-500", sku: "PMP-500", label: "500 g", pricePaise: 39900, comparePricePaise: 49900, stock: 35 },
    ],
  },
  {
    id: "sunflower-seeds-id",
    slug: "sunflower-seeds",
    name: "Raw Sunflower Seeds",
    categorySlug: "seeds",
    description: "Gently cleaned whole sunflower seeds without shells.",
    imageUrl: "/assets/shop-sunflower-seeds.jpg",
    variants: [
      { id: "sun-250", sku: "SUN-250", label: "250 g", pricePaise: 17900, comparePricePaise: 21900, stock: 40 },
      { id: "sun-500", sku: "SUN-500", label: "500 g", pricePaise: 32900, comparePricePaise: 39900, stock: 30 },
    ],
  },
  {
    id: "healthy-mix-id",
    slug: "healthy-mix",
    name: "Royal Daily Healthy Mix",
    categorySlug: "healthy-mix",
    description: "Supercharged energizing trail mix of dry fruits and seeds.",
    imageUrl: "/assets/shop-mixed-nuts.jpg",
    variants: [
      { id: "mix-250", sku: "MIX-250", label: "250 g", pricePaise: 36900, comparePricePaise: 42900, stock: 40 },
      { id: "mix-500", sku: "MIX-500", label: "500 g", pricePaise: 69900, comparePricePaise: 82900, stock: 30 },
    ],
  },
  {
    id: "festive-gift-box-id",
    slug: "festive-gift-box",
    name: "Festive Dry Fruits Gift Box",
    categorySlug: "gift-boxes",
    description: "Luxury artisan wooden gift hamper with 4 individual canisters.",
    imageUrl: "/assets/mixed-fruit-reference.jpg",
    variants: [
      { id: "gift-1kg", sku: "GIFT-1KG", label: "1 kg Deluxe Hamper", pricePaise: 189900, comparePricePaise: 229900, stock: 30 },
    ],
  },
  {
    id: "organic-walnuts-id",
    slug: "organic-walnuts",
    name: "Organic Himalayan Walnuts",
    categorySlug: "organic",
    description: "Certified 100% organic handpicked wild walnuts from Kashmir.",
    imageUrl: "/assets/collection-walnuts.jpg",
    variants: [
      { id: "org-wal-250", sku: "ORG-WAL-250", label: "250 g", pricePaise: 49900, comparePricePaise: 57900, stock: 25 },
      { id: "org-wal-500", sku: "ORG-WAL-500", label: "500 g", pricePaise: 94900, comparePricePaise: 109900, stock: 20 },
    ],
  },
];

type Params = { category?: string };

export default async function Shop({ searchParams }: { searchParams: Promise<Params> }) {
  const raw = await searchParams;
  let dbProducts: ShopProduct[] = [];

  try {
    const all = await listProducts();
    const sorted = [...all].sort((a, b) => a.name.localeCompare(b.name));
    dbProducts = sorted
      .filter((p) => !p.isDemo)
      .map((p) => ({
        id: p.id,
        slug: p.slug,
        name: p.name,
        description: p.description,
        imageUrl: p.imageUrl || "/assets/shop-pistachios-hd.jpg",
        categorySlug: p.category?.slug || "nuts",
        variants: p.variants.map((v) => ({
          id: v.id,
          sku: v.sku,
          label: v.label,
          pricePaise: v.pricePaise,
          comparePricePaise: v.comparePricePaise,
          stock: v.stock,
        })),
      }));
  } catch (err) {
    console.error("Failed to list products from database:", err);
  }

  if (dbProducts.length === 0) {
    dbProducts = DEFAULT_PRODUCTS;
  }

  let dbCategories: { id: string; slug: string; name: string; imageUrl?: string | null }[] = [];
  try {
    const cats = await listCategories();
    dbCategories = cats.map((c) => ({ id: c.id, slug: c.slug, name: c.name, imageUrl: c.imageUrl }));
  } catch {
    // fallback
  }

  if (dbCategories.length === 0) {
    dbCategories = DEFAULT_CATEGORIES;
  }

  return (
    <ShopCatalog
      initialProducts={dbProducts}
      initialCategories={dbCategories}
      activeCategorySlug={raw.category}
    />
  );
}
