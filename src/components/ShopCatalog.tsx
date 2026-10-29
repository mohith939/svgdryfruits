"use client";

import { useState, useRef } from "react";
import Link from "next/link";
import { motion, AnimatePresence } from "framer-motion";
import { Icon } from "./Icon";
import { useCart, addToCart, setQuantity } from "@/lib/cart";
import { triggerCartToast } from "./CartToast";
import { formatINR } from "@/lib/money";

export interface ShopVariant {
  id: string;
  sku: string;
  label: string;
  pricePaise: number;
  comparePricePaise?: number | null;
  stock: number;
}

export interface ShopProduct {
  id: string;
  slug: string;
  name: string;
  description?: string | null;
  imageUrl?: string | null;
  categorySlug?: string;
  badge?: string;
  variants: ShopVariant[];
}

export interface CategoryTabItem {
  id: string;
  label: string;
  icon: string;
}

interface ShopCatalogProps {
  initialProducts: ShopProduct[];
  activeCategorySlug?: string;
  initialCategories?: { id: string; slug: string; name: string; imageUrl?: string | null }[];
}

const DEFAULT_CATEGORY_ICONS: Record<string, string> = {
  nuts: "/assets/cat-hd-nuts.png",
  "dried-fruits": "/assets/cat-hd-dried-fruits.png",
  seeds: "/assets/cat-hd-seeds.png",
  "healthy-mix": "/assets/cat-hd-healthy-mix.png",
  combos: "/assets/cat-hd-gift-boxes.png",
  "gift-boxes": "/assets/cat-hd-gift-boxes.png",
};

const FALLBACK_CATEGORY_TABS: CategoryTabItem[] = [
  { id: "all", label: "All Products", icon: "/assets/cat-hd-all-products.png" },
  { id: "nuts", label: "Nuts", icon: "/assets/cat-hd-nuts.png" },
  { id: "dried-fruits", label: "Dried Fruits", icon: "/assets/cat-hd-dried-fruits.png" },
  { id: "seeds", label: "Seeds", icon: "/assets/cat-hd-seeds.png" },
  { id: "healthy-mix", label: "Healthy Mix", icon: "/assets/cat-hd-healthy-mix.png" },
  { id: "combos", label: "Combos", icon: "/assets/cat-hd-gift-boxes.png" },
];

export function ShopCatalog({ initialProducts, activeCategorySlug, initialCategories }: ShopCatalogProps) {
  const [selectedCat, setSelectedCat] = useState<string>(activeCategorySlug || "all");
  const [selectedSizes, setSelectedSizes] = useState<Record<string, number>>({});
  const [wishlist, setWishlist] = useState<Set<string>>(new Set());
  const cart = useCart();

  // Drag-to-scroll and arrow navigation ref & state
  const tabsContainerRef = useRef<HTMLDivElement>(null);
  const [isMouseDown, setIsMouseDown] = useState(false);
  const startXRef = useRef(0);
  const scrollLeftRef = useRef(0);
  const hasDraggedRef = useRef(false);

  const handleMouseDown = (e: React.MouseEvent) => {
    if (!tabsContainerRef.current) return;
    setIsMouseDown(true);
    hasDraggedRef.current = false;
    startXRef.current = e.pageX - tabsContainerRef.current.offsetLeft;
    scrollLeftRef.current = tabsContainerRef.current.scrollLeft;
  };

  const handleMouseLeave = () => {
    setIsMouseDown(false);
  };

  const handleMouseUp = () => {
    setIsMouseDown(false);
  };

  const handleMouseMove = (e: React.MouseEvent) => {
    if (!isMouseDown || !tabsContainerRef.current) return;
    e.preventDefault();
    const x = e.pageX - tabsContainerRef.current.offsetLeft;
    const walk = (x - startXRef.current) * 1.5;
    if (Math.abs(walk) > 8) {
      hasDraggedRef.current = true;
    }
    tabsContainerRef.current.scrollLeft = scrollLeftRef.current - walk;
  };

  const scrollTabs = (direction: "left" | "right") => {
    if (!tabsContainerRef.current) return;
    const distance = 300;
    tabsContainerRef.current.scrollBy({
      left: direction === "left" ? -distance : distance,
      behavior: "smooth",
    });
  };

  // Build dynamic category tabs: "All Products" + DB categories with images
  const categoryTabs: CategoryTabItem[] = initialCategories && initialCategories.length > 0
    ? [
        { id: "all", label: "All Products", icon: "/assets/cat-hd-all-products.png" },
        ...initialCategories.map((c) => ({
          id: c.slug,
          label: c.name,
          icon: c.imageUrl || DEFAULT_CATEGORY_ICONS[c.slug] || "/assets/cat-hd-nuts.png",
        })),
      ]
    : FALLBACK_CATEGORY_TABS;

  // Filter products based on active category
  const filteredProducts = initialProducts.filter((p) => {
    if (selectedCat === "all") return true;
    return p.categorySlug === selectedCat;
  });

  const handleSelectSize = (slug: string, variantIndex: number) => {
    setSelectedSizes((prev) => ({ ...prev, [slug]: variantIndex }));
  };

  const toggleWishlist = (productId: string) => {
    setWishlist((prev) => {
      const next = new Set(prev);
      if (next.has(productId)) {
        next.delete(productId);
      } else {
        next.add(productId);
      }
      return next;
    });
  };

  const handleAdd = (product: ShopProduct, variant: ShopVariant) => {
    addToCart(variant.id, 1, {
      productName: product.name,
      variantLabel: variant.label,
      imageUrl: product.imageUrl,
      unitPricePaise: variant.pricePaise,
    });
    triggerCartToast({
      title: product.name,
      size: variant.label,
      qty: 1,
    });
  };

  const handleIncrement = (product: ShopProduct, variant: ShopVariant, currentQty: number) => {
    addToCart(variant.id, 1, {
      productName: product.name,
      variantLabel: variant.label,
      imageUrl: product.imageUrl,
      unitPricePaise: variant.pricePaise,
    });
    triggerCartToast({
      title: product.name,
      size: variant.label,
      qty: currentQty + 1,
    });
  };

  const handleDecrement = (variantId: string, currentQty: number) => {
    setQuantity(variantId, currentQty - 1);
  };

  return (
    <div className="eoc-page">
      {/* =====================================================================
          SECTION HEADING: "Explore Our Collection"
          ===================================================================== */}
      <div className="eoc-heading-wrap">
        {/* Decorative leaf icon */}
        <span className="eoc-leaf-icon" aria-hidden="true">
          <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M14 3C14 3 6 8 6 16C6 20.4183 9.58172 24 14 24C18.4183 24 22 20.4183 22 16C22 8 14 3 14 3Z" fill="#A77052" opacity="0.85"/>
            <path d="M14 3L14 24" stroke="#0E3622" strokeWidth="1.5" strokeLinecap="round"/>
            <path d="M14 12C14 12 10 14 8 18" stroke="#0E3622" strokeWidth="1.2" strokeLinecap="round" opacity="0.6"/>
          </svg>
        </span>
        <h1 className="eoc-heading">
          <span className="eoc-heading-dark">Explore Our </span>
          <span className="eoc-heading-orange">Collection</span>
        </h1>
      </div>

      {/* =====================================================================
          CATEGORY TABS — Draggable Horizontal Row with Smooth Scroll Arrows
          ===================================================================== */}
      <div className="eoc-tabs-outer" style={{ position: "relative", maxWidth: 1240, margin: "0 auto 40px", padding: "0 16px" }}>
        {/* Left Arrow Button */}
        <button
          type="button"
          onClick={() => scrollTabs("left")}
          aria-label="Scroll categories left"
          className="eoc-tabs-arrow eoc-tabs-arrow-left"
          style={{
            position: "absolute",
            left: 2,
            top: "50%",
            transform: "translateY(-50%)",
            zIndex: 10,
            width: 38,
            height: 38,
            borderRadius: "50%",
            background: "#FFFFFF",
            border: "1.5px solid #D6C8B5",
            boxShadow: "0 4px 14px rgba(0,0,0,0.12)",
            color: "#073D26",
            fontSize: 20,
            fontWeight: 700,
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            cursor: "pointer",
            transition: "all 0.2s ease",
          }}
        >
          ‹
        </button>

        <div
          ref={tabsContainerRef}
          className={`eoc-tabs-wrap${isMouseDown ? " is-dragging" : ""}`}
          onMouseDown={handleMouseDown}
          onMouseLeave={handleMouseLeave}
          onMouseUp={handleMouseUp}
          onMouseMove={handleMouseMove}
          style={{
            cursor: isMouseDown ? "grabbing" : "grab",
            userSelect: "none",
            WebkitUserSelect: "none",
            padding: "8px 24px 18px",
            scrollBehavior: isMouseDown ? "auto" : "smooth",
          }}
        >
          <div className="eoc-tabs-row" role="tablist" aria-label="Product categories">
            {categoryTabs.map((tab) => {
              const isActive = selectedCat === tab.id;
              return (
                <button
                  key={tab.id}
                  type="button"
                  role="tab"
                  aria-selected={isActive}
                  onClick={() => {
                    if (!hasDraggedRef.current) {
                      setSelectedCat(tab.id);
                    }
                  }}
                  className={`eoc-tab-card${isActive ? " is-active" : ""}`}
                >
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img
                    src={tab.icon}
                    alt={tab.label}
                    className="eoc-tab-card-img"
                    aria-hidden="true"
                    draggable={false}
                    style={{ pointerEvents: "none" }}
                  />
                  <span className="eoc-tab-card-label" style={{ pointerEvents: "none" }}>{tab.label}</span>
                  {isActive && <span className="eoc-tab-active-bar" />}
                </button>
              );
            })}
          </div>
        </div>

        {/* Right Arrow Button */}
        <button
          type="button"
          onClick={() => scrollTabs("right")}
          aria-label="Scroll categories right"
          className="eoc-tabs-arrow eoc-tabs-arrow-right"
          style={{
            position: "absolute",
            right: 2,
            top: "50%",
            transform: "translateY(-50%)",
            zIndex: 10,
            width: 38,
            height: 38,
            borderRadius: "50%",
            background: "#FFFFFF",
            border: "1.5px solid #D6C8B5",
            boxShadow: "0 4px 14px rgba(0,0,0,0.12)",
            color: "#073D26",
            fontSize: 20,
            fontWeight: 700,
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            cursor: "pointer",
            transition: "all 0.2s ease",
          }}
        >
          ›
        </button>
      </div>

      {/* =====================================================================
          PRODUCT CARDS GRID
          ===================================================================== */}
      <main className="eoc-grid-wrap">
        <motion.div layout className="eoc-cards-grid">
          <AnimatePresence>
            {filteredProducts.length > 0 ? (
              filteredProducts.map((p, i) => {
                const vIndex = selectedSizes[p.slug] ?? 0;
                const currentVariant = p.variants[vIndex] || p.variants[0];
                const cartItem = cart.find((item) => item.variantId === currentVariant?.id);
                const qtyInCart = cartItem ? cartItem.quantity : 0;
                const isWishlisted = wishlist.has(p.id);

                return (
                  <motion.article
                    key={p.id}
                    layout
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, scale: 0.95 }}
                    transition={{ duration: 0.3, delay: Math.min(i * 0.04, 0.28) }}
                    className="eoc-card"
                  >
                    {/* ── Image ── */}
                    <div className="eoc-card-img-wrap">
                      <Link
                        href={`/products/${p.slug}`}
                        className="eoc-card-img-link"
                        aria-label={`View ${p.name}`}
                        tabIndex={-1}
                      >
                        {/* eslint-disable-next-line @next/next/no-img-element */}
                        <img
                          src={p.imageUrl || "/assets/shop-pistachios-hd.jpg"}
                          alt={p.name}
                          className="eoc-card-img"
                          loading="lazy"
                        />
                      </Link>

                      {/* Heart / Wishlist button */}
                      <button
                        type="button"
                        onClick={() => toggleWishlist(p.id)}
                        className={`eoc-wishlist-btn${isWishlisted ? " is-wishlisted" : ""}`}
                        aria-label={isWishlisted ? `Remove ${p.name} from wishlist` : `Add ${p.name} to wishlist`}
                      >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill={isWishlisted ? "#E05252" : "none"} stroke={isWishlisted ? "#E05252" : "#888"} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                          <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                        </svg>
                      </button>
                    </div>

                    {/* ── Body ── */}
                    <div className="eoc-card-body">
                      <h2 className="eoc-card-name">
                        <Link href={`/products/${p.slug}`}>{p.name}</Link>
                      </h2>

                      {/* Size Pills */}
                      <div
                        className="eoc-card-sizes"
                        role="group"
                        aria-label={`Select weight for ${p.name}`}
                      >
                        {p.variants.map((v, idx) => {
                          const isSel = idx === vIndex;
                          return (
                            <button
                              key={v.id}
                              type="button"
                              onClick={() => handleSelectSize(p.slug, idx)}
                              className={`eoc-size-pill${isSel ? " is-selected" : ""}`}
                              aria-pressed={isSel}
                            >
                              {v.label}
                            </button>
                          );
                        })}
                      </div>

                      {/* Footer: Price + Cart Action */}
                      <div className="eoc-card-footer">
                        <span className="eoc-card-price">
                          {currentVariant ? formatINR(currentVariant.pricePaise) : "₹—"}
                        </span>

                        <div className="eoc-cart-action">
                          <AnimatePresence mode="wait">
                            {qtyInCart === 0 ? (
                              <motion.button
                                key="add"
                                type="button"
                                initial={{ opacity: 0, scale: 0.88 }}
                                animate={{ opacity: 1, scale: 1 }}
                                exit={{ opacity: 0, scale: 0.88 }}
                                whileTap={{ scale: 0.95 }}
                                onClick={() => currentVariant && handleAdd(p, currentVariant)}
                                className="eoc-add-btn"
                                aria-label={`Add ${p.name} to cart`}
                              >
                                <Icon name="cart" size={15} />
                                <span>Add to Cart</span>
                              </motion.button>
                            ) : (
                              <motion.div
                                key="qty"
                                initial={{ opacity: 0, scale: 0.88 }}
                                animate={{ opacity: 1, scale: 1 }}
                                exit={{ opacity: 0, scale: 0.88 }}
                                className="eoc-qty-pill"
                              >
                                <button
                                  type="button"
                                  onClick={() => handleDecrement(currentVariant.id, qtyInCart)}
                                  className="eoc-qty-btn"
                                  aria-label="Decrease quantity"
                                >
                                  −
                                </button>
                                <span className="eoc-qty-num" aria-live="polite">
                                  {qtyInCart}
                                </span>
                                <button
                                  type="button"
                                  onClick={() => currentVariant && handleIncrement(p, currentVariant, qtyInCart)}
                                  className="eoc-qty-btn"
                                  aria-label="Increase quantity"
                                >
                                  +
                                </button>
                              </motion.div>
                            )}
                          </AnimatePresence>
                        </div>
                      </div>
                    </div>
                  </motion.article>
                );
              })
            ) : (
              <div className="eoc-empty-box">
                <p className="eoc-empty-msg">
                  {selectedCat === "all" ? (
                    "No products are currently available in the catalog."
                  ) : (
                    <>
                      No products found in <strong>{categoryTabs.find((c) => c.id === selectedCat)?.label || selectedCat}</strong>.
                    </>
                  )}
                </p>
                {selectedCat !== "all" && (
                  <button
                    type="button"
                    onClick={() => setSelectedCat("all")}
                    className="eoc-empty-btn"
                  >
                    Explore All Products
                  </button>
                )}
              </div>
            )}
          </AnimatePresence>
        </motion.div>
      </main>
    </div>
  );
}
