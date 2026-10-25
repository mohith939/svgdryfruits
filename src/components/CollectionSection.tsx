"use client";

import { useState } from "react";
import Link from "next/link";
import { motion } from "motion/react";
import { Icon } from "./Icon";
import { addToCart } from "@/lib/cart";
import { triggerCartToast } from "./CartToast";
import { formatINR } from "@/lib/money";

interface VariantInfo {
  id: string;
  label: string;
  pricePaise: number;
  comparePricePaise: number;
}

interface ProductItem {
  slug: string;
  name: string;
  category: string;
  img: string;
  variants: VariantInfo[];
}

const CATEGORIES = [
  { id: "nuts", label: "Nuts", icon: "/assets/cat-icon-nuts.png" },
  { id: "seeds", label: "Seeds", icon: "/assets/cat-icon-seeds.png" },
  { id: "dried-fruits", label: "Dried Fruits", icon: "/assets/cat-icon-dried-fruits.png" },
  { id: "premium-mix", label: "Premium Mix", icon: "/assets/cat-icon-premium-mix.png" },
  { id: "gift-packs", label: "Gift Packs", icon: "/assets/cat-icon-gift-packs.png" },
  { id: "organic", label: "Organic", icon: "/assets/cat-icon-organic.png" },
];

const PRODUCTS: ProductItem[] = [
  {
    slug: "almonds",
    name: "Almonds",
    category: "nuts",
    img: "/assets/collection-almonds.jpg",
    variants: [
      { id: "a9ab8dd5-bada-4b33-ac34-9a9fe34007c9", label: "250g", pricePaise: 29900, comparePricePaise: 34900 },
      { id: "5158048f-0eec-4592-8cb9-9aaf2d091c55", label: "500g", pricePaise: 56900, comparePricePaise: 64900 },
      { id: "ea47aeef-1602-4067-ae98-5d3250479e89", label: "1kg", pricePaise: 109900, comparePricePaise: 124900 },
    ],
  },
  {
    slug: "cashews",
    name: "Cashews",
    category: "nuts",
    img: "/assets/collection-cashews.jpg",
    variants: [
      { id: "a2eb5922-94ba-4b28-aea2-1a023bfa66dc", label: "250g", pricePaise: 34900, comparePricePaise: 39900 },
      { id: "842a011f-9091-4a2a-a185-b7f64bd81b40", label: "500g", pricePaise: 67900, comparePricePaise: 75900 },
      { id: "5bc86f1f-8aa1-4241-8db8-7ca2cbda5295", label: "1kg", pricePaise: 129900, comparePricePaise: 149900 },
    ],
  },
  {
    slug: "pistachios",
    name: "Pistachios",
    category: "nuts",
    img: "/assets/collection-pistachios.jpg",
    variants: [
      { id: "6f96de5b-783c-4d18-81b3-1108e46d3f5e", label: "250g", pricePaise: 39900, comparePricePaise: 44900 },
      { id: "c9365324-78ed-45e0-826b-53b3d57b75a8", label: "500g", pricePaise: 76900, comparePricePaise: 85900 },
      { id: "6b8f9828-60e6-4188-b2da-17251ef9b947", label: "1kg", pricePaise: 149900, comparePricePaise: 169900 },
    ],
  },
  {
    slug: "walnuts",
    name: "Walnuts",
    category: "nuts",
    img: "/assets/collection-walnuts.jpg",
    variants: [
      { id: "45a3d9e0-01ed-4c57-97fa-0f32b7863807", label: "250g", pricePaise: 34900, comparePricePaise: 39900 },
      { id: "1c9d9adc-0282-4fc7-a5ce-b84d234b2a9e", label: "500g", pricePaise: 66900, comparePricePaise: 74900 },
      { id: "0171a418-8ed9-4954-82eb-2f2255c1951f", label: "1kg", pricePaise: 129900, comparePricePaise: 144900 },
    ],
  },
  {
    slug: "raisins",
    name: "Raisins",
    category: "dried-fruits",
    img: "/assets/collection-raisins.jpg",
    variants: [
      { id: "57cb407d-7fa8-4e77-8a57-0ea594a26d24", label: "250g", pricePaise: 24900, comparePricePaise: 29900 },
      { id: "05f46ad3-d9a5-437e-b5fb-e74195ba9a8f", label: "500g", pricePaise: 46900, comparePricePaise: 52900 },
      { id: "68780f3b-9335-4c99-8e08-ae1436478a6e", label: "1kg", pricePaise: 89900, comparePricePaise: 99900 },
    ],
  },
];

export function CollectionSection() {
  const [activeCat, setActiveCat] = useState("nuts");
  const [selectedVariants, setSelectedVariants] = useState<Record<string, number>>({
    almonds: 0,
    cashews: 0,
    pistachios: 0,
    walnuts: 0,
    raisins: 0,
  });
  const [favorites, setFavorites] = useState<Record<string, boolean>>({});

  function handleSelectVariant(slug: string, index: number) {
    setSelectedVariants((prev) => ({ ...prev, [slug]: index }));
  }

  function toggleFavorite(slug: string) {
    setFavorites((prev) => ({ ...prev, [slug]: !prev[slug] }));
  }

  function handleAdd(product: ProductItem) {
    const vIndex = selectedVariants[product.slug] ?? 0;
    const v = product.variants[vIndex];
    if (!v) return;
    addToCart(v.id, 1);
    triggerCartToast({
      title: product.name,
      size: v.label,
      qty: 1,
    });
  }

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

        {/* 6 Category Filter Tabs */}
        <div className="coll-tabs" role="tablist" aria-label="Product categories">
          {CATEGORIES.map((cat) => {
            const isActive = activeCat === cat.id;
            return (
              <button
                key={cat.id}
                type="button"
                role="tab"
                aria-selected={isActive}
                className={`coll-tab-btn${isActive ? " is-active" : ""}`}
                onClick={() => setActiveCat(cat.id)}
              >
                <div className="coll-tab-icon-wrap">
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img src={cat.icon} alt="" className="coll-tab-icon-img" />
                </div>
                <span className="coll-tab-label">{cat.label}</span>
                {isActive && <motion.div layoutId="coll-active-line" className="coll-tab-indicator" />}
              </button>
            );
          })}
        </div>

        {/* 5 Product Cards Grid (No descriptions per user request) */}
        <div className="coll-grid">
          {PRODUCTS.map((prod, index) => {
            const vIndex = selectedVariants[prod.slug] ?? 0;
            const currentVariant = prod.variants[vIndex] ?? prod.variants[0];
            const isFav = favorites[prod.slug] ?? false;

            return (
              <motion.div
                key={prod.slug}
                className="coll-card"
                initial={{ opacity: 0, y: 24 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true, margin: "-40px" }}
                transition={{ duration: 0.5, delay: index * 0.08, ease: [0.22, 1, 0.36, 1] }}
              >
                <div className="coll-card-media">
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img src={prod.img} alt={prod.name} className="coll-card-img" />
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
                  <div className="coll-card-price-row">
                    <span className="coll-card-price">{formatINR(currentVariant.pricePaise)}</span>
                    {currentVariant.comparePricePaise > currentVariant.pricePaise && (
                      <s className="coll-card-compare">{formatINR(currentVariant.comparePricePaise)}</s>
                    )}
                  </div>

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
        </div>

        {/* View All Products CTA */}
        <div className="coll-footer-cta">
          <Link href="/shop" className="coll-view-all-btn">
            View All Products <span aria-hidden="true">→</span>
          </Link>
        </div>
      </div>
    </section>
  );
}
