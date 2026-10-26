"use client";

import { useState } from "react";
import Link from "next/link";
import { motion, AnimatePresence } from "motion/react";
import { Icon } from "./Icon";
import { addToCart } from "@/lib/cart";
import { triggerCartToast } from "./CartToast";
import { formatINR } from "@/lib/money";

export interface CollectionVariant {
  id: string;
  label: string;
  pricePaise: number;
  comparePricePaise?: number | null;
}

export interface CollectionProduct {
  id: string;
  slug: string;
  name: string;
  imageUrl?: string | null;
  categorySlug?: string;
  variants: CollectionVariant[];
}

export interface CollectionCategory {
  id: string;
  slug: string;
  name: string;
  imageUrl?: string | null;
}

interface CollectionSectionProps {
  initialProducts?: CollectionProduct[];
  initialCategories?: CollectionCategory[];
}

export function CollectionSection({
  initialProducts = [],
  initialCategories = [],
}: CollectionSectionProps) {
  const [activeCat, setActiveCat] = useState<string>(
    initialCategories[0]?.slug || "all"
  );
  const [selectedVariants, setSelectedVariants] = useState<Record<string, number>>({});
  const [favorites, setFavorites] = useState<Record<string, boolean>>({});

  function handleSelectVariant(slug: string, index: number) {
    setSelectedVariants((prev) => ({ ...prev, [slug]: index }));
  }

  function toggleFavorite(slug: string) {
    setFavorites((prev) => ({ ...prev, [slug]: !prev[slug] }));
  }

  function handleAdd(product: CollectionProduct) {
    const vIndex = selectedVariants[product.slug] ?? 0;
    const v = product.variants[vIndex] ?? product.variants[0];
    if (!v) return;
    addToCart(v.id, 1, {
      productName: product.name,
      variantLabel: v.label,
      imageUrl: product.imageUrl,
      unitPricePaise: v.pricePaise,
    });
    triggerCartToast({
      title: product.name,
      size: v.label,
      qty: 1,
    });
  }

  // Filter products by active category
  const filteredProducts =
    activeCat === "all" || initialCategories.length === 0
      ? initialProducts
      : initialProducts.filter((p) => p.categorySlug === activeCat);

  return (
    <section className="coll-section" aria-labelledby="coll-title">
      {/* 4 Animated Floating Corner Ingredients */}
      <div className="coll-floating-bg" aria-hidden="true">
        {/* Top-Left: Almonds & Leaf */}
        <motion.div
          className="coll-float-corner coll-float-tl"
          animate={{
            y: [0, -9, 3, 0],
            x: [0, 4, -2, 0],
            rotate: [0, 1.4, -0.8, 0],
          }}
          transition={{
            duration: 6.2,
            repeat: Infinity,
            ease: "easeInOut",
          }}
        >
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src="/assets/sec2-ingredient-top-left.png" alt="" />
        </motion.div>

        {/* Top-Right: Pistachio Bowl & Leaves */}
        <motion.div
          className="coll-float-corner coll-float-tr"
          animate={{
            y: [0, -10, 4, 0],
            x: [0, -5, 2, 0],
            rotate: [0, -1.6, 0.6, 0],
          }}
          transition={{
            duration: 7.1,
            repeat: Infinity,
            ease: "easeInOut",
            delay: 0.8,
          }}
        >
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src="/assets/sec2-ingredient-top-right.png" alt="" />
        </motion.div>

        {/* Bottom-Left: Walnuts & Leaves */}
        <motion.div
          className="coll-float-corner coll-float-bl"
          animate={{
            y: [0, -8, 3, 0],
            x: [0, 5, -2, 0],
            rotate: [0, 1.2, -1.0, 0],
          }}
          transition={{
            duration: 6.8,
            repeat: Infinity,
            ease: "easeInOut",
            delay: 1.6,
          }}
        >
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src="/assets/sec2-ingredient-bottom-left.png" alt="" />
        </motion.div>

        {/* Bottom-Right: Cashews & Leaves */}
        <motion.div
          className="coll-float-corner coll-float-br"
          animate={{
            y: [0, -9, 2, 0],
            x: [0, -4, 3, 0],
            rotate: [0, -1.3, 0.9, 0],
          }}
          transition={{
            duration: 6.5,
            repeat: Infinity,
            ease: "easeInOut",
            delay: 2.2,
          }}
        >
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src="/assets/sec2-ingredient-bottom-right.png" alt="" />
        </motion.div>
      </div>

      <div className="coll-inner">
        {/* Header (No subtitles per user request) */}
        <div className="coll-header">
          <p className="coll-kicker">— OUR COLLECTION —</p>
          <h2 id="coll-title" className="coll-title">
            Premium Dry Fruits <em>for Every Occasion</em>
          </h2>
        </div>

        {/* Category Filter Tabs (if categories exist) */}
        {initialCategories.length > 0 && (
          <div className="coll-tabs" role="tablist" aria-label="Product categories">
            {initialCategories.map((cat) => {
              const isActive = activeCat === cat.slug;
              return (
                <button
                  key={cat.slug}
                  type="button"
                  role="tab"
                  aria-selected={isActive}
                  className={`coll-tab-btn${isActive ? " is-active" : ""}`}
                  onClick={() => setActiveCat(cat.slug)}
                >
                  {cat.imageUrl && (
                    <div className="coll-tab-icon-wrap">
                      {/* eslint-disable-next-line @next/next/no-img-element */}
                      <img src={cat.imageUrl} alt="" className="coll-tab-icon-img" />
                    </div>
                  )}
                  <span className="coll-tab-label">{cat.name}</span>
                  {isActive && <motion.div layoutId="coll-active-line" className="coll-tab-indicator" />}
                </button>
              );
            })}
          </div>
        )}

        {/* Empty state when 0 products exist */}
        {initialProducts.length === 0 ? (
          <div style={{ textAlign: "center", padding: "50px 20px 30px", color: "#665C50" }}>
            <p style={{ fontFamily: "var(--font-serif)", fontSize: 24, fontStyle: "italic", marginBottom: 12, color: "#0E3626" }}>
              Fresh Harvest Arriving Soon
            </p>
            <p style={{ fontSize: 15, maxWidth: 520, margin: "0 auto 24px", color: "#7C7265", lineHeight: 1.6 }}>
              Our collection is currently being prepared. Check back shortly to explore our newly added artisanal dry fruits, crunchy nuts, and gift boxes.
            </p>
            <Link href="/contact" className="coll-view-all-btn">
              Contact Store for Orders <span aria-hidden="true">→</span>
            </Link>
          </div>
        ) : (
          /* Product Cards Grid */
          <div className="coll-grid">
            <AnimatePresence mode="popLayout">
              {filteredProducts.map((prod, index) => {
                const vIndex = selectedVariants[prod.slug] ?? 0;
                const currentVariant = prod.variants[vIndex] ?? prod.variants[0];
                const isFav = favorites[prod.slug] ?? false;

                return (
                  <motion.div
                    key={prod.slug}
                    className="coll-card"
                    initial={{ opacity: 0, y: 16 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, scale: 0.96 }}
                    transition={{ duration: 0.35, delay: index * 0.05, ease: [0.22, 1, 0.36, 1] }}
                  >
                    <div className="coll-card-media">
                      {/* eslint-disable-next-line @next/next/no-img-element */}
                      <img src={prod.imageUrl || "/assets/pdp-hero-almonds.jpg"} alt={prod.name} className="coll-card-img" />
                      <button
                        type="button"
                        className={`coll-card-fav-btn${isFav ? " is-favorited" : ""}`}
                        aria-label={`Save ${prod.name} to wishlist`}
                        onClick={() => toggleFavorite(prod.slug)}
                      >
                        <Icon name="heart" size={16} />
                      </button>
                    </div>

                    <div className="coll-card-body">
                      <h3 className="coll-card-title">
                        <Link href={`/products/${prod.slug}`}>{prod.name}</Link>
                      </h3>

                      {/* Pack size pills */}
                      <div className="coll-card-sizes" role="radiogroup" aria-label="Pack sizes">
                        {prod.variants.map((v, i) => (
                          <button
                            key={v.id}
                            type="button"
                            role="radio"
                            aria-checked={i === vIndex}
                            className={`coll-size-pill${i === vIndex ? " is-active" : ""}`}
                            onClick={() => handleSelectVariant(prod.slug, i)}
                          >
                            {v.label}
                          </button>
                        ))}
                      </div>

                      {/* Price Row */}
                      {currentVariant && (
                        <div className="coll-card-price-row">
                          <span className="coll-card-price">{formatINR(currentVariant.pricePaise)}</span>
                          {currentVariant.comparePricePaise && currentVariant.comparePricePaise > currentVariant.pricePaise && (
                            <s className="coll-card-compare">{formatINR(currentVariant.comparePricePaise)}</s>
                          )}
                        </div>
                      )}

                      {/* Add to Bag Button */}
                      <motion.button
                        type="button"
                        className="coll-card-add-btn"
                        whileHover={{ scale: 1.02 }}
                        whileTap={{ scale: 0.97 }}
                        onClick={() => handleAdd(prod)}
                      >
                        <Icon name="bag" size={18} />
                        <span>Add to Bag</span>
                      </motion.button>
                    </div>
                  </motion.div>
                );
              })}
            </AnimatePresence>
          </div>
        )}

        {/* View All Products CTA (only if products exist) */}
        {initialProducts.length > 0 && (
          <div className="coll-footer-cta">
            <Link href="/shop" className="coll-view-all-btn">
              View All Products <span aria-hidden="true">→</span>
            </Link>
          </div>
        )}
      </div>
    </section>
  );
}
