import { notFound } from "next/navigation";
import type { Metadata } from "next";
import { getProduct } from "@/lib/catalog";
import { findCatalogProduct } from "@/lib/catalog-data";
import { ProductDetailView, type ProductDetailData } from "@/components/ProductDetailView";

export const revalidate = 60;

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const p = await getProduct(slug).catch(() => null);
  const title = p ? p.name : slug.split("-").map((s) => s.charAt(0).toUpperCase() + s.slice(1)).join(" ");
  return {
    title: `${title} — SVG Dry Fruits`,
    description: p?.description || "Wholesome nuts, dried fruits and natural foods from SVG Dry Fruits.",
  };
}

export default async function ProductPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  let dbProduct = null;
  try {
    dbProduct = await getProduct(slug);
  } catch (err) {
    console.warn("ProductPage getProduct failed:", err);
  }

  if (!dbProduct) {
    notFound();
  }

  const productData: ProductDetailData = {
    id: dbProduct.id,
    slug: dbProduct.slug,
    name: dbProduct.name,
    categoryName: dbProduct.category?.name || "Dry Fruits",
    categorySlug: dbProduct.category?.slug,
    description: dbProduct.description,
    imageUrl: dbProduct.imageUrl,
    images: dbProduct.images,
    variants: dbProduct.variants.map((v) => ({
      id: v.id,
      sku: v.sku,
      label: v.label,
      pricePaise: v.pricePaise,
      comparePricePaise: v.comparePricePaise,
      stock: v.stock,
    })),
  };

  return <ProductDetailView product={productData} />;
}
