import Link from "next/link";
import { listProducts } from "@/lib/catalog";
import { ProductGrid } from "@/components/ProductGrid";

export const dynamic = "force-dynamic";

export default async function Combos() {
  const items = await listProducts({ combosOnly: true });
  return (
    <>
      <section className="shop-intro">
        <div className="breadcrumbs"><Link href="/">Home</Link><span aria-hidden="true">/</span><span>Gifts &amp; combos</span></div>
        <div className="shop-intro-row"><h1>Gifts &amp; <em>combos.</em></h1><p>Thoughtful boxes for the people you remember.</p></div>
      </section>
      <section className="catalog-section"><ProductGrid items={items} /></section>
    </>
  );
}
