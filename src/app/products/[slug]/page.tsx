import { notFound } from "next/navigation";
import type { Metadata } from "next";
import { getProduct } from "@/lib/catalog";
import { ProductDetailView, type ProductDetailData } from "@/components/ProductDetailView";

export const dynamic = "force-dynamic";

// Fallback product data if not found in db or during cold starts
const FALLBACK_PRODUCTS: Record<string, ProductDetailData> = {
  almonds: {
    id: "almonds-id",
    slug: "almonds",
    name: "California Almonds",
    categoryName: "Almonds",
    categorySlug: "nuts",
    description:
      "Naturally nutritious, crunchy and incredibly versatile — our California Almonds are sourced from trusted farms, carefully selected for their size, taste and quality.",
    imageUrl: "/assets/pdp-hero-almonds.jpg",
    variants: [
      { id: "alm-250", sku: "ALM-250", label: "250 g", pricePaise: 34900, comparePricePaise: 39900, stock: 50 },
      { id: "alm-500", sku: "ALM-500", label: "500 g", pricePaise: 67900, comparePricePaise: 76900, stock: 40 },
      { id: "alm-1kg", sku: "ALM-1KG", label: "1 kg", pricePaise: 129900, comparePricePaise: 149900, stock: 30 },
    ],
  },
  pistachios: {
    id: "pistachios-id",
    slug: "pistachios",
    name: "Premium Pistachios",
    categoryName: "Pistachios",
    categorySlug: "nuts",
    description:
      "Naturally opened, gently slow-roasted jumbo California pistachios. Sourced from trusted growers for unbeatable freshness, rich crunch, and vibrant green kernels.",
    imageUrl: "/assets/shop-pistachios-hd.jpg",
    variants: [
      { id: "pist-250", sku: "PIST-250", label: "250 g", pricePaise: 39900, comparePricePaise: 47900, stock: 25 },
      { id: "pist-500", sku: "PIST-500", label: "500 g", pricePaise: 76900, comparePricePaise: 92900, stock: 20 },
      { id: "pist-1kg", sku: "PIST-1KG", label: "1 kg", pricePaise: 149900, comparePricePaise: 179900, stock: 15 },
    ],
  },
};

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  let p = null;
  try {
    p = await getProduct(slug);
  } catch (err) {
    console.warn("generateMetadata getProduct failed:", err);
  }
  const title = p ? p.name : FALLBACK_PRODUCTS[slug]?.name || "Product";
  return {
    title: `${title} — SVG Dry Fruits`,
    description: p?.description || FALLBACK_PRODUCTS[slug]?.description || undefined,
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

  let productData: ProductDetailData;

  if (dbProduct) {
    productData = {
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
  } else if (FALLBACK_PRODUCTS[slug]) {
    productData = FALLBACK_PRODUCTS[slug];
  } else {
    // If not found in db or fallbacks, check if it matches a general product name
    const titleCase = slug.split("-").map((s) => s.charAt(0).toUpperCase() + s.slice(1)).join(" ");
    productData = {
      id: `${slug}-id`,
      slug,
      name: titleCase,
      categoryName: "Dry Fruits",
      imageUrl: "/assets/pdp-hero-almonds.jpg",
      variants: [
        { id: `${slug}-250`, sku: `${slug.toUpperCase()}-250`, label: "250 g", pricePaise: 39900, comparePricePaise: 47900, stock: 25 },
        { id: `${slug}-500`, sku: `${slug.toUpperCase()}-500`, label: "500 g", pricePaise: 76900, comparePricePaise: 92900, stock: 20 },
        { id: `${slug}-1kg`, sku: `${slug.toUpperCase()}-1KG`, label: "1 kg", pricePaise: 149900, comparePricePaise: 179900, stock: 15 },
      ],
    };
  }

  // Ensure default variants if product had 0 variants
  if (!productData.variants || productData.variants.length === 0) {
    productData.variants = [
      { id: `${slug}-250`, sku: `${slug.toUpperCase()}-250`, label: "250 g", pricePaise: 39900, comparePricePaise: 47900, stock: 25 },
      { id: `${slug}-500`, sku: `${slug.toUpperCase()}-500`, label: "500 g", pricePaise: 76900, comparePricePaise: 92900, stock: 20 },
      { id: `${slug}-1kg`, sku: `${slug.toUpperCase()}-1KG`, label: "1 kg", pricePaise: 149900, comparePricePaise: 179900, stock: 15 },
    ];
  }

  return <ProductDetailView product={productData} />;
}
