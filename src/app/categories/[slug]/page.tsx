import Link from "next/link";
import { notFound } from "next/navigation";
import { listCategories, listProducts } from "@/lib/catalog";
import { ProductGrid } from "@/components/ProductGrid";

export const dynamic = "force-dynamic";

export default async function Category({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const cat = (await listCategories()).find((c) => c.slug === slug);
  if (!cat) notFound();
  const items = await listProducts({ categorySlug: slug });
  return (
    <>
      <section className="shop-intro">
        <div className="breadcrumbs"><Link href="/">Home</Link><span aria-hidden="true">/</span><Link href="/shop">Shop</Link><span aria-hidden="true">/</span><span>{cat.name}</span></div>
        <div className="shop-intro-row"><h1>{cat.name}</h1><p>{cat.description}</p></div>
      </section>
      <section className="catalog-section"><ProductGrid items={items} /></section>
    </>
  );
}
