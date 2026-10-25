"use client";

import Link from "next/link";
import { motion } from "motion/react";
import { formatINR } from "@/lib/money";
import type { listProducts } from "@/lib/catalog";

type P = Awaited<ReturnType<typeof listProducts>>[number];

export function ProductGrid({ items }: { items: P[] }) {
  if (!items.length) return <p className="empty-results">No products found. Try another search or category.</p>;

  return (
    <div className="catalog-grid">
      {items.map((p, i) => {
        const inStock = p.variants.some((v) => v.stock > 0);
        const from = p.variants[0]?.pricePaise;

        return (
          <motion.article
            className="catalog-card"
            key={p.id}
            initial={{ opacity: 0, y: 24 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: "-40px" }}
            transition={{ duration: 0.45, delay: Math.min(i * 0.08, 0.4), ease: [0.22, 1, 0.36, 1] }}
            whileHover={{ y: -6, transition: { duration: 0.25, ease: "easeOut" } }}
          >
            <Link className="catalog-image" href={`/products/${p.slug}`} aria-label={p.name}>
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <motion.img
                whileHover={{ scale: 1.05 }}
                transition={{ duration: 0.4, ease: "easeOut" }}
                src={p.imageUrl ?? "/assets/mixed-nuts-reference.jpg"}
                alt=""
                loading="lazy"
              />
              <span className="catalog-image-index">{String(i + 1).padStart(2, "0")}</span>
              <span className="catalog-image-arrow" aria-hidden="true">↗</span>
              {p.isDemo && <span className="image-reference-tag">DEMO ITEM</span>}
            </Link>
            <div className="catalog-card-meta">
              <span>{(p.category?.name ?? "GIFTING").toUpperCase()}</span>
              <span>{inStock ? "IN STOCK" : "SOLD OUT"}</span>
            </div>
            <h3>{p.name}</h3>
            <p>{p.description}</p>
            <span className="catalog-detail">{from !== undefined ? `From ${formatINR(from)}` : "Unavailable"}</span>
          </motion.article>
        );
      })}
    </div>
  );
}
