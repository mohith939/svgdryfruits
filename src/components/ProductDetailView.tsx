"use client";

import { useState } from "react";
import Link from "next/link";
import { motion, AnimatePresence } from "framer-motion";
import { formatINR } from "@/lib/money";
import { addToCart } from "@/lib/cart";
import { triggerCartToast } from "./CartToast";

export interface ProductVariantData {
  id: string;
  sku: string;
  label: string;
  pricePaise: number;
  comparePricePaise?: number | null;
  stock: number;
}

export interface ProductDetailData {
  id: string;
  slug: string;
  name: string;
  categoryName?: string;
  categorySlug?: string;
  description?: string | null;
  imageUrl?: string | null;
  images?: string[] | null;
  variants: ProductVariantData[];
}

interface ProductDetailViewProps {
  product: ProductDetailData;
}

// Product-specific customized stories, nutrition, and FAQs
const PRODUCT_INFO_MAP: Record<
  string,
  {
    kicker: string;
    displayTitle: string;
    badgeText: string[];
    lede: string;
    bannerKicker: string;
    bannerTitle: string;
    bannerDesc: string;
    bannerFeatures: { title: string; desc: string }[];
    orchardTitle: string;
    orchardDesc: string;
    orchardPillars: string[];
    waysImages: { src: string; caption: string }[];
    nutritionDesc: string;
    nutrients: { label: string; desc: string; icon: string }[];
    faqs: { q: string; a: string }[];
    galleryImages: string[];
  }
> = {
  almonds: {
    kicker: "ALMONDS",
    displayTitle: "California Almonds (Premium Grade)",
    badgeText: ["PREMIUM", "CALIFORNIA", "ALMONDS"],
    lede: "Naturally nutritious, crunchy and incredibly versatile — our California Almonds are sourced from trusted farms, carefully selected for their size, taste and quality.",
    bannerKicker: "GOODNESS IN EVERY BITE",
    bannerTitle: "Pure. Wholesome. Naturally Better.",
    bannerDesc: "Sourced from the finest California farms, our almonds are rich in nutrients, naturally crunchy and perfect for your everyday nutrition.",
    bannerFeatures: [
      { title: "Rich in Nutrients", desc: "Packed with plant power" },
      { title: "Supports Heart Health", desc: "Healthy natural monounsaturated fats" },
      { title: "Great for Daily Snacking", desc: "Wholesome energy on the go" },
    ],
    orchardTitle: "FROM NATURE'S FINEST ORCHARDS",
    orchardDesc: "Our almonds are sourced directly from trusted California farms, known for their superior quality and perfect growing conditions. Each batch is carefully selected, cleaned and packed to ensure you get only the best.",
    orchardPillars: ["PREMIUM FARMS", "CAREFULLY SELECTED", "PACKED FOR FRESHNESS"],
    waysImages: [
      { src: "/assets/pdp-way-snack-hd.jpg", caption: "Snack on the go" },
      { src: "/assets/pdp-way-smoothie-hd.jpg", caption: "Add to your smoothies" },
      { src: "/assets/pdp-way-cookies-hd.jpg", caption: "Use in your favourite recipes" },
    ],
    nutritionDesc: "Almonds are packed with essential nutrients that support your overall well-being.",
    nutrients: [
      { label: "Vitamin E", desc: "Supports healthy skin", icon: "leaf" },
      { label: "Healthy Fats", desc: "Good for heart health", icon: "heart" },
      { label: "Protein", desc: "Helps in muscle repair", icon: "fitness" },
      { label: "Fibre", desc: "Supports digestion", icon: "wheat" },
    ],
    faqs: [
      {
        q: "Where are these almonds sourced from?",
        a: "Our almonds are sourced directly from verified, certified farms in California, renowned for their ideal Mediterranean climate, nutrient-rich soils, and sustainable harvesting practices.",
      },
      {
        q: "Are these almonds raw or roasted?",
        a: "These are 100% natural, raw California almonds with no added oils, preservatives, or artificial flavours. They can be consumed raw, soaked overnight, or gently roasted at home.",
      },
      {
        q: "How should I store almonds?",
        a: "To maintain maximum crunch, natural oils, and freshness, store them in an airtight container in a cool, dry place away from direct sunlight. Refrigeration can extend shelf life up to 12 months.",
      },
      {
        q: "Do you offer bulk orders?",
        a: "Yes! We supply cafes, bakeries, corporate gifting programs, and families with bulk quantities. Please reach out via our Bulk Orders page or contact our team directly for special wholesale pricing.",
      },
    ],
    galleryImages: [
      "/assets/pdp-hero-almonds.jpg",
      "/assets/pdp-thumb-1.jpg",
      "/assets/pdp-thumb-2.jpg",
      "/assets/pdp-thumb-3.jpg",
    ],
  },
  pistachios: {
    kicker: "PISTACHIOS",
    displayTitle: "Premium California Pistachios (King Size)",
    badgeText: ["PREMIUM", "CALIFORNIA", "PISTACHIOS"],
    lede: "Vibrant green, naturally opened, and gently slow-roasted with just a hint of natural salt. Sourced from the finest California groves for unbeatable freshness and rich taste.",
    bannerKicker: "GOODNESS IN EVERY CRACK",
    bannerTitle: "Pure. Wholesome. Naturally Better.",
    bannerDesc: "Grown in sun-drenched orchards, our California pistachios are packed with plant protein, heart-healthy fats, and rich minerals for a nourishing daily snack.",
    bannerFeatures: [
      { title: "Rich in Nutrients", desc: "Abundant in Vitamin B6 & Potassium" },
      { title: "Supports Heart Health", desc: "Natural antioxidants and phytosterols" },
      { title: "Great for Daily Snacking", desc: "Satisfying crunch anytime" },
    ],
    orchardTitle: "FROM NATURE'S FINEST ORCHARDS",
    orchardDesc: "Our pistachios are cultivated in fertile valley orchards with long sunny days and cool nights. Hand-inspected and sorted so only naturally split, jumbo kernels make it into your jar.",
    orchardPillars: ["PREMIUM FARMS", "CAREFULLY SELECTED", "PACKED FOR FRESHNESS"],
    waysImages: [
      { src: "/assets/pdp-way-snack-hd.jpg", caption: "Snack on the go" },
      { src: "/assets/pdp-way-smoothie-hd.jpg", caption: "Add to your salads & bowls" },
      { src: "/assets/pdp-way-cookies-hd.jpg", caption: "Garnish desserts & sweets" },
    ],
    nutritionDesc: "Pistachios are nutrient-dense powerhouses providing clean protein and essential minerals.",
    nutrients: [
      { label: "Vitamin B6", desc: "Energy & metabolism support", icon: "leaf" },
      { label: "Healthy Fats", desc: "Promotes cardiovascular wellness", icon: "heart" },
      { label: "Plant Protein", desc: "Sustained daily endurance", icon: "fitness" },
      { label: "Antioxidants", desc: "Rich in lutein and zeaxanthin", icon: "wheat" },
    ],
    faqs: [
      {
        q: "Where are these pistachios sourced from?",
        a: "Our pistachios are sourced from premium California growers known for adhering to the highest standards of soil health, natural drying, and strict sorting.",
      },
      {
        q: "Are these pistachios raw or roasted?",
        a: "These are lightly dry-roasted with shells naturally opened on the branch, giving you maximum crunch without greasy oils or heavy artificial additives.",
      },
      {
        q: "How should I store pistachios?",
        a: "Keep them sealed in an airtight jar in a cool, dark cupboard. In warm or humid seasons, storing them in the refrigerator keeps the shells and nut kernels crisp.",
      },
      {
        q: "Do you offer bulk orders?",
        a: "Yes, we offer custom packaging and discounted bulk slabs (5 kg, 10 kg, and custom corporate gift boxes). Contact our team via WhatsApp or the Contact page.",
      },
    ],
    galleryImages: [
      "/assets/shop-pistachios-hd.jpg",
      "/assets/pdp-hero-almonds.jpg",
      "/assets/pdp-thumb-1.jpg",
      "/assets/pdp-thumb-3.jpg",
    ],
  },
};

export function ProductDetailView({ product }: ProductDetailViewProps) {
  const [selectedVariantIdx, setSelectedVariantIdx] = useState(0);
  const [qty, setQty] = useState(1);
  const [activeImgIdx, setActiveImgIdx] = useState(0);
  const [openFaq, setOpenFaq] = useState<number | null>(0); // First FAQ open by default

  const currentVariant = product.variants[selectedVariantIdx] || product.variants[0];

  // Lookup custom metadata or generate smart dynamic content
  const info =
    PRODUCT_INFO_MAP[product.slug] || {
      kicker: (product.categoryName || "DRY FRUITS").toUpperCase(),
      displayTitle: `${product.name} (Premium Grade)`,
      badgeText: ["PREMIUM", "SELECTED", product.name.toUpperCase().split(" ")[0]],
      lede:
        product.description ||
        `Naturally nutritious, crunchy and pure — our ${product.name} are carefully sourced from trusted growers, selected for exceptional taste and quality.`,
      bannerKicker: "GOODNESS IN EVERY BITE",
      bannerTitle: "Pure. Wholesome. Naturally Better.",
      bannerDesc: `Sourced from the finest growers, our ${product.name.toLowerCase()} are rich in natural nutrients, wholesome and perfect for your everyday vitality.`,
      bannerFeatures: [
        { title: "Rich in Nutrients", desc: "Packed with clean vitamins" },
        { title: "Supports Heart Health", desc: "Wholesome natural nourishment" },
        { title: "Great for Daily Snacking", desc: "Nutritious daily routine" },
      ],
      orchardTitle: "FROM NATURE'S FINEST ORCHARDS",
      orchardDesc: `Our ${product.name.toLowerCase()} are sourced directly from trusted growers, carefully selected, cleaned, and sealed to ensure peak freshness in every pack.`,
      orchardPillars: ["PREMIUM FARMS", "CAREFULLY SELECTED", "PACKED FOR FRESHNESS"],
      waysImages: [
        { src: "/assets/pdp-way-snack-hd.jpg", caption: "Snack on the go" },
        { src: "/assets/pdp-way-smoothie-hd.jpg", caption: "Add to your smoothies" },
        { src: "/assets/pdp-way-cookies-hd.jpg", caption: "Use in your favourite recipes" },
      ],
      nutritionDesc: `${product.name} are packed with essential vitamins and clean nutrients that support overall vitality.`,
      nutrients: [
        { label: "Essential Nutrients", desc: "Clean cellular energy", icon: "leaf" },
        { label: "Healthy Fats", desc: "Supports cardiovascular health", icon: "heart" },
        { label: "Natural Protein", desc: "Builds and repairs tissue", icon: "fitness" },
        { label: "Dietary Fibre", desc: "Supports healthy digestion", icon: "wheat" },
      ],
      faqs: [
        {
          q: `Where are these ${product.name.toLowerCase()} sourced from?`,
          a: `We partner with certified farms and growers who prioritize sustainable agriculture, pristine hygiene, and natural harvesting.`,
        },
        {
          q: `Are these ${product.name.toLowerCase()} processed with additives?`,
          a: `No, all SVG Dry Fruits are free from synthetic preservatives, artificial colouring, and chemical sprays.`,
        },
        {
          q: `How should I store them?`,
          a: `Store in a cool, dry place inside an airtight glass or ceramic container away from direct sunlight.`,
        },
        {
          q: `Do you offer bulk orders?`,
          a: `Yes! We provide bulk pricing for events, corporate gifting, and wholesale inquiries. Contact us for custom rates.`,
        },
      ],
      galleryImages: [
        product.imageUrl || "/assets/pdp-hero-almonds.jpg",
        "/assets/pdp-thumb-1.jpg",
        "/assets/pdp-thumb-2.jpg",
        "/assets/pdp-thumb-3.jpg",
      ],
    };

  const images = (product.images && product.images.length > 0)
    ? product.images
    : (info.galleryImages && info.galleryImages.length > 0
      ? info.galleryImages
      : [product.imageUrl || "/assets/pdp-hero-almonds.jpg"]);
  const currentImg = images[activeImgIdx] || images[0] || product.imageUrl || "/assets/pdp-hero-almonds.jpg";

  // Dynamic pricing calculations
  const price = currentVariant ? currentVariant.pricePaise : 129000;
  const comparePrice = currentVariant?.comparePricePaise || Math.round(price * 1.15);
  const discountPercent =
    comparePrice > price ? Math.round(((comparePrice - price) / comparePrice) * 100) : 13;

  const handleAddToCart = () => {
    if (!currentVariant) return;
    addToCart(currentVariant.id, qty, {
      productName: product.name,
      variantLabel: currentVariant.label,
      imageUrl: product.imageUrl,
      unitPricePaise: currentVariant.pricePaise,
    });
    triggerCartToast({
      title: product.name,
      size: currentVariant.label,
      qty,
    });
  };

  const toggleFaq = (index: number) => {
    setOpenFaq((prev) => (prev === index ? null : index));
  };

  return (
    <div className="pdp-v2-page">
      {/* =====================================================================
          SECTION 1: HERO PDP (GALLERY + BUY BOX)
          ===================================================================== */}
      <section className="pdp-v2-hero">
        <div className="pdp-v2-container pdp-v2-hero-grid">
          {/* ── Left Column: Vertical Thumbnails + Main Image ── */}
          <div className="pdp-v2-gallery">
            {/* Vertical thumbnails column with up/down arrows */}
            <div className="pdp-v2-thumbs-col">
              <button
                type="button"
                className="pdp-v2-thumb-arrow"
                aria-label="Previous thumbnail"
                onClick={() =>
                  setActiveImgIdx((prev) => (prev > 0 ? prev - 1 : images.length - 1))
                }
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                  <polyline points="18 15 12 9 6 15" />
                </svg>
              </button>

              <div className="pdp-v2-thumbs-list">
                {images.map((imgSrc, i) => {
                  const isActive = i === activeImgIdx;
                  return (
                    <button
                      key={i}
                      type="button"
                      onClick={() => setActiveImgIdx(i)}
                      className={`pdp-v2-thumb-btn${isActive ? " is-active" : ""}`}
                      aria-label={`View photo ${i + 1}`}
                    >
                      {/* eslint-disable-next-line @next/next/no-img-element */}
                      <img src={imgSrc} alt="" className="pdp-v2-thumb-img" />
                    </button>
                  );
                })}
              </div>

              <button
                type="button"
                className="pdp-v2-thumb-arrow"
                aria-label="Next thumbnail"
                onClick={() =>
                  setActiveImgIdx((prev) => (prev < images.length - 1 ? prev + 1 : 0))
                }
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                  <polyline points="6 9 12 15 18 9" />
                </svg>
              </button>
            </div>

            {/* Main Hero Photo with circular badge overlay */}
            <div className="pdp-v2-main-image-wrap">
              <AnimatePresence mode="wait">
                <motion.img
                  key={currentImg}
                  src={currentImg}
                  alt={product.name}
                  initial={{ opacity: 0.6 }}
                  animate={{ opacity: 1 }}
                  exit={{ opacity: 0.6 }}
                  transition={{ duration: 0.25 }}
                  className="pdp-v2-main-img"
                />
              </AnimatePresence>

              {/* Circular Overlay Badge */}
              <div className="pdp-v2-circle-badge" aria-hidden="true">
                {info.badgeText.map((line, idx) => (
                  <span key={idx} className="pdp-v2-circle-line">
                    {line}
                  </span>
                ))}
              </div>
            </div>
          </div>

          {/* ── Right Column: Product Info & Actions ── */}
          <div className="pdp-v2-info">
            {/* Category Kicker */}
            <span className="pdp-v2-kicker">{info.kicker}</span>

            {/* Product Title (Editorial Serif) */}
            <h1 className="pdp-v2-title">{info.displayTitle}</h1>

            {/* Price Row: Current Price + Strikethrough + Discount Badge */}
            <div className="pdp-v2-price-row">
              <span className="pdp-v2-price-current">{formatINR(price)}</span>
              {comparePrice > price && (
                <span className="pdp-v2-price-compare">{formatINR(comparePrice)}</span>
              )}
              {discountPercent > 0 && (
                <span className="pdp-v2-save-pill">Save {discountPercent}%</span>
              )}
            </div>
            <p className="pdp-v2-tax-subtext">Inclusive of all taxes</p>

            {/* Description Lede */}
            <p className="pdp-v2-lede">{info.lede}</p>

            {/* Weight / Variant Selection */}
            <div className="pdp-v2-variants-block">
              <span className="pdp-v2-variants-label">Weight</span>
              <div className="pdp-v2-variants-row" role="radiogroup" aria-label="Select weight">
                {product.variants.map((v, i) => {
                  const isSelected = i === selectedVariantIdx;
                  return (
                    <button
                      key={v.id}
                      type="button"
                      role="radio"
                      aria-checked={isSelected}
                      onClick={() => {
                        setSelectedVariantIdx(i);
                        setQty(1);
                      }}
                      className={`pdp-v2-weight-pill${isSelected ? " is-active" : ""}`}
                    >
                      {v.label}
                    </button>
                  );
                })}
              </div>
            </div>

            {/* Quantity Controller & Add to Cart Action */}
            <div className="pdp-v2-actions-row">
              <div className="pdp-v2-qty-box" aria-label="Select quantity">
                <button
                  type="button"
                  onClick={() => setQty((prev) => Math.max(1, prev - 1))}
                  className="pdp-v2-qty-btn"
                  aria-label="Decrease quantity"
                  disabled={qty <= 1}
                >
                  −
                </button>
                <span className="pdp-v2-qty-display" aria-live="polite">
                  {qty}
                </span>
                <button
                  type="button"
                  onClick={() => setQty((prev) => prev + 1)}
                  className="pdp-v2-qty-btn"
                  aria-label="Increase quantity"
                >
                  +
                </button>
              </div>

              <motion.button
                type="button"
                whileTap={{ scale: 0.98 }}
                onClick={handleAddToCart}
                className="pdp-v2-add-btn"
              >
                Add to Cart
              </motion.button>
            </div>

            {/* 4 Trust Badges in a Horizontal Row */}
            <div className="pdp-v2-trust-grid">
              <div className="pdp-v2-trust-item">
                <div className="pdp-v2-trust-icon-box">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0E3622" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z" />
                    <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12" />
                  </svg>
                </div>
                <span className="pdp-v2-trust-label">100% Natural</span>
              </div>

              <div className="pdp-v2-trust-item">
                <div className="pdp-v2-trust-icon-box">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0E3622" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                    <polyline points="9 12 11 14 15 10" />
                  </svg>
                </div>
                <span className="pdp-v2-trust-label">Premium Grade</span>
              </div>

              <div className="pdp-v2-trust-item">
                <div className="pdp-v2-trust-icon-box">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0E3622" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round">
                    <rect x="1" y="3" width="15" height="13" />
                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8" />
                    <circle cx="5.5" cy="18.5" r="2.5" />
                    <circle cx="18.5" cy="18.5" r="2.5" />
                  </svg>
                </div>
                <span className="pdp-v2-trust-label">Fast Delivery</span>
              </div>

              <div className="pdp-v2-trust-item">
                <div className="pdp-v2-trust-icon-box">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0E3622" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                  </svg>
                </div>
                <span className="pdp-v2-trust-label">No Preservatives</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* =====================================================================
          SECTION 2: GOODNESS IN EVERY BITE (BANNER)
          ===================================================================== */}
      <section className="pdp-v2-banner-sec">
        <div className="pdp-v2-container pdp-v2-banner-grid">
          <div className="pdp-v2-banner-content">
            <span className="pdp-v2-banner-kicker">{info.bannerKicker}</span>
            <h2 className="pdp-v2-banner-title">{info.bannerTitle}</h2>
            <p className="pdp-v2-banner-desc">{info.bannerDesc}</p>

            <div className="pdp-v2-banner-badges">
              {info.bannerFeatures.map((feat, idx) => (
                <div key={idx} className="pdp-v2-feat-item">
                  <div className="pdp-v2-feat-circle">
                    {idx === 0 && (
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0E3622" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M12 2L15 8L21 9L17 14L18 20L12 17L6 20L7 14L3 9L9 8L12 2Z" />
                      </svg>
                    )}
                    {idx === 1 && (
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0E3622" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                      </svg>
                    )}
                    {idx === 2 && (
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0E3622" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                      </svg>
                    )}
                  </div>
                  <span className="pdp-v2-feat-text">{feat.title}</span>
                </div>
              ))}
            </div>
          </div>

          <div className="pdp-v2-banner-media">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img
              src="/assets/pdp-banner-hd.jpg"
              alt="Scattered nuts close-up"
              className="pdp-v2-banner-img"
              loading="lazy"
            />
          </div>
        </div>
      </section>

      {/* =====================================================================
          SECTION 3: FROM NATURE'S FINEST ORCHARDS
          ===================================================================== */}
      <section className="pdp-v2-orchard-sec">
        <div className="pdp-v2-container pdp-v2-orchard-grid">
          <div className="pdp-v2-orchard-media">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img
              src="/assets/pdp-orchard-hd.jpg"
              alt="Orchard trees with fresh harvest"
              className="pdp-v2-orchard-img"
              loading="lazy"
            />
          </div>

          <div className="pdp-v2-orchard-content">
            <h2 className="pdp-v2-orchard-title">{info.orchardTitle}</h2>
            <p className="pdp-v2-orchard-desc">{info.orchardDesc}</p>

            <div className="pdp-v2-orchard-line" />

            <div className="pdp-v2-orchard-pillars">
              {info.orchardPillars.map((pillar, i) => (
                <div key={i} className="pdp-v2-pillar-item">
                  <span className="pdp-v2-pillar-label">{pillar}</span>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* =====================================================================
          SECTION 4: WAYS TO ENJOY (SIMPLE INGREDIENTS. ENDLESS POSSIBILITIES.)
          ===================================================================== */}
      <section className="pdp-v2-ways-sec">
        <div className="pdp-v2-container pdp-v2-ways-grid">
          <div className="pdp-v2-ways-header">
            <span className="pdp-v2-ways-kicker">WAYS TO ENJOY</span>
            <h2 className="pdp-v2-ways-title">
              Simple Ingredients.
              <br />
              Endless Possibilities.
            </h2>
          </div>

          <div className="pdp-v2-ways-cards">
            {info.waysImages.map((way, idx) => (
              <div key={idx} className="pdp-v2-way-card">
                <div className="pdp-v2-way-img-wrap">
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img src={way.src} alt={way.caption} className="pdp-v2-way-img" loading="lazy" />
                </div>
                <span className="pdp-v2-way-caption">{way.caption}</span>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* =====================================================================
          SECTION 5: NUTRITION INFORMATION
          ===================================================================== */}
      <section className="pdp-v2-nutrition-sec">
        <div className="pdp-v2-container pdp-v2-nutrition-grid">
          <div className="pdp-v2-nutrition-media">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img
              src="/assets/pdp-nutrition-hd.jpg"
              alt="Nutrient-dense natural dry fruits in ceramic bowl"
              className="pdp-v2-nutrition-img"
              loading="lazy"
            />
          </div>

          <div className="pdp-v2-nutrition-content">
            <span className="pdp-v2-nutrition-kicker">NUTRITION INFORMATION</span>
            <h2 className="pdp-v2-nutrition-title">
              A Natural Source of
              <br />
              Good Nutrition
            </h2>
            <p className="pdp-v2-nutrition-desc">{info.nutritionDesc}</p>

            <div className="pdp-v2-nutrition-line" />

            <div className="pdp-v2-nutrients-grid">
              {info.nutrients.map((n, idx) => (
                <div key={idx} className="pdp-v2-nutrient-item">
                  <div className="pdp-v2-nutrient-icon">
                    {n.icon === "leaf" && (
                      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0E3622" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z" />
                        <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12" />
                      </svg>
                    )}
                    {n.icon === "heart" && (
                      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0E3622" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                      </svg>
                    )}
                    {n.icon === "fitness" && (
                      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0E3622" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M6 5v14M18 5v14M2 9v6M22 9v6M6 12h12" />
                      </svg>
                    )}
                    {n.icon === "wheat" && (
                      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0E3622" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M2 22 12 12M12 2v20M8 6l4 4M16 6l-4 4M8 12l4 4M16 12l-4 4" />
                      </svg>
                    )}
                  </div>
                  <div className="pdp-v2-nutrient-text">
                    <span className="pdp-v2-nutrient-name">{n.label}</span>
                    <span className="pdp-v2-nutrient-sub">{n.desc}</span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* =====================================================================
          SECTION 6: FREQUENTLY ASKED QUESTIONS (ACCORDION)
          ===================================================================== */}
      <section className="pdp-v2-faq-sec">
        <div className="pdp-v2-container pdp-v2-faq-wrap">
          <div className="pdp-v2-faq-header">
            <span className="pdp-v2-faq-kicker">FREQUENTLY ASKED QUESTIONS</span>
            <h2 className="pdp-v2-faq-title">Everything You Need to Know</h2>
            <div className="pdp-v2-faq-line" />
          </div>

          <div className="pdp-v2-faq-list">
            {info.faqs.map((faq, idx) => {
              const isOpen = openFaq === idx;
              return (
                <div key={idx} className="pdp-v2-faq-item">
                  <button
                    type="button"
                    onClick={() => toggleFaq(idx)}
                    className="pdp-v2-faq-question"
                    aria-expanded={isOpen}
                  >
                    <span>{faq.q}</span>
                    <svg
                      width="18"
                      height="18"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeWidth="2"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      className={`pdp-v2-faq-chevron${isOpen ? " is-open" : ""}`}
                    >
                      <polyline points="6 9 12 15 18 9" />
                    </svg>
                  </button>

                  <AnimatePresence>
                    {isOpen && (
                      <motion.div
                        initial={{ opacity: 0, height: 0 }}
                        animate={{ opacity: 1, height: "auto" }}
                        exit={{ opacity: 0, height: 0 }}
                        transition={{ duration: 0.22 }}
                        className="pdp-v2-faq-answer"
                      >
                        <p>{faq.a}</p>
                      </motion.div>
                    )}
                  </AnimatePresence>
                </div>
              );
            })}
          </div>
        </div>
      </section>
    </div>
  );
}
