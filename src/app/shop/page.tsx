import type { Metadata } from "next";
import { listCategories, listProducts } from "@/lib/catalog";
import { ShopCatalog, type ShopProduct } from "@/components/ShopCatalog";
import { REAL_CATEGORIES, REAL_PRODUCTS } from "@/lib/catalog-data";

export const dynamic = "force-dynamic";
export const metadata: Metadata = {
  title: "Explore Our Collection — SVG Dry Fruits",
  description: "Wholesome nuts, dried fruits and natural foods, carefully sourced from trusted growers.",
};

const DEFAULT_CATEGORIES = REAL_CATEGORIES.map((c) => ({
  id: c.id,
  slug: c.slug,
  name: c.name,
  imageUrl: c.imageUrl,
}));

const DEFAULT_PRODUCTS: ShopProduct[] = REAL_PRODUCTS.map((p) => ({
  id: p.id,
  slug: p.slug,
  name: p.name,
  categorySlug: p.categorySlug,
  description: p.description,
  imageUrl: p.imageUrl,
  variants: p.variants.map((v) => ({
    id: v.id,
    sku: v.sku,
    label: v.label,
    pricePaise: v.pricePaise,
    comparePricePaise: v.comparePricePaise,
    stock: v.stock,
  })),
}));

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
