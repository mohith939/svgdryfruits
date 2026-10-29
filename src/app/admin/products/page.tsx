import Link from "next/link";
import { redirect } from "next/navigation";
import { asc, eq, inArray } from "drizzle-orm";
import { getDb } from "@/db/client";
import { categories, products, variants } from "@/db/schema";
import { getAdminSession } from "@/lib/auth";
import { formatINR } from "@/lib/money";
import { AdminProductRowActions } from "@/components/AdminProductRowActions";
import { AdminCatalogControls } from "@/components/AdminCatalogControls";

export const dynamic = "force-dynamic";

export default async function AdminProductsPage({
  searchParams,
}: {
  searchParams: Promise<{ q?: string; category?: string }>;
}) {
  const session = await getAdminSession();
  if (!session) redirect("/admin/login");

  const { q, category } = await searchParams;
  const db = await getDb();

  let allCategories = await db.select().from(categories).orderBy(asc(categories.name));

  let rows = await db
    .select({
      product: products,
      category: categories,
    })
    .from(products)
    .leftJoin(categories, eq(products.categoryId, categories.id))
    .orderBy(asc(products.name));

  const productIds = rows.map((r) => r.product.id);
  const allVariants = productIds.length > 0
    ? await db
        .select()
        .from(variants)
        .where(inArray(variants.productId, productIds))
        .orderBy(asc(variants.pricePaise))
    : [];

  const variantsByProductId = new Map<string, typeof allVariants>();
  for (const v of allVariants) {
    const list = variantsByProductId.get(v.productId) ?? [];
    list.push(v);
    variantsByProductId.set(v.productId, list);
  }

  const allItems = rows.map((r) => ({
    ...r.product,
    category: r.category,
    variants: variantsByProductId.get(r.product.id) ?? [],
  }));

  // Filter in memory for instantaneous search/filtering
  const filtered = allItems.filter((p) => {
    if (q) {
      const matchName = p.name.toLowerCase().includes(q.toLowerCase());
      const matchSlug = p.slug.toLowerCase().includes(q.toLowerCase());
      const matchSku = p.variants.some((v) => v.sku.toLowerCase().includes(q.toLowerCase()));
      if (!matchName && !matchSlug && !matchSku) return false;
    }
    if (category && p.category?.slug !== category) return false;
    return true;
  });

  // KPI Metrics
  const totalProducts = allItems.length;
  const totalStockUnits = allItems.reduce(
    (acc, p) => acc + p.variants.reduce((sum, v) => sum + v.stock, 0),
    0
  );
  const lowStockCount = allItems.filter((p) =>
    p.variants.some((v) => v.stock <= v.lowStockThreshold && v.stock > 0)
  ).length;

  return (
    <div style={{ maxWidth: 1240, margin: "0 auto", padding: "28px 24px 80px" }}>
      {/* Header Banner */}
      <div className="admin-v3-header-wrap">
        <div className="admin-v3-header-left">
          <div className="admin-v3-breadcrumbs">
            <Link href="/admin">Dashboard</Link>
            <span aria-hidden="true">/</span>
            <span>Products &amp; Inventory</span>
          </div>
          <h1 className="admin-v3-title">
            Products &amp; <em>Inventory</em>
          </h1>
          <p className="admin-v3-subtitle">
            Easily manage pack sizes, selling prices in INR, inventory stock, and product status.
          </p>
        </div>

        <div className="admin-v3-header-script" aria-hidden="true">
          <span className="admin-v3-script-text">
            Real Ingredients.<br />
            Brighter Days.
          </span>
        </div>
      </div>

      {/* 4 KPI Metric Cards */}
      <div className="admin-v3-kpi-grid">
        {/* Card 1: Total Products */}
        <div className="admin-v3-kpi-card">
          <div className="admin-v3-kpi-left">
            <div className="admin-v3-kpi-icon-box" style={{ background: "#EAF5EC", color: "#2E7D32" }}>
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                <path d="m3.3 7 8.7 5 8.7-5" />
                <path d="M12 22V12" />
              </svg>
            </div>
            <div className="admin-v3-kpi-info">
              <span className="admin-v3-kpi-num">{totalProducts}</span>
              <span className="admin-v3-kpi-label">TOTAL PRODUCTS</span>
            </div>
          </div>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src="/assets/singlebadam.png" alt="Badam" className="admin-kpi-thumb" />
        </div>

        {/* Card 2: Total Stock Units */}
        <div className="admin-v3-kpi-card">
          <div className="admin-v3-kpi-left">
            <div className="admin-v3-kpi-icon-box" style={{ background: "#F7F1E5", color: "#8E6D38" }}>
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                <ellipse cx="12" cy="5" rx="9" ry="3" />
                <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3" />
                <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" />
              </svg>
            </div>
            <div className="admin-v3-kpi-info">
              <span className="admin-v3-kpi-num">{totalStockUnits}</span>
              <span className="admin-v3-kpi-label">TOTAL STOCK UNITS</span>
            </div>
          </div>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src="/assets/singlepistaproduct.png" alt="Pista" className="admin-kpi-thumb" />
        </div>

        {/* Card 3: Low-Stock Warnings */}
        <div className="admin-v3-kpi-card">
          <div className="admin-v3-kpi-left">
            <div className="admin-v3-kpi-icon-box" style={{ background: "#FEF6E9", color: "#B4691B" }}>
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
                <line x1="12" y1="9" x2="12" y2="13" />
                <line x1="12" y1="17" x2="12.01" y2="17" />
              </svg>
            </div>
            <div className="admin-v3-kpi-info">
              <span className="admin-v3-kpi-num" style={{ color: lowStockCount > 0 ? "#B4691B" : "#1E2520" }}>
                {lowStockCount}
              </span>
              <span className="admin-v3-kpi-label">LOW-STOCK WARNINGS</span>
            </div>
          </div>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src="/assets/acrod%20image.png" alt="Akhrot" className="admin-kpi-thumb" />
        </div>

        {/* Card 4: Catalog Categories */}
        <div className="admin-v3-kpi-card">
          <div className="admin-v3-kpi-left">
            <div className="admin-v3-kpi-icon-box" style={{ background: "#EAF5EC", color: "#2E7D32" }}>
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" />
                <line x1="7" y1="7" x2="7.01" y2="7" />
              </svg>
            </div>
            <div className="admin-v3-kpi-info">
              <span className="admin-v3-kpi-num">{allCategories.length}</span>
              <span className="admin-v3-kpi-label">CATALOG CATEGORIES</span>
            </div>
          </div>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src="/assets/drydate%20image.png" alt="Dry Dates" className="admin-kpi-thumb" />
        </div>
      </div>

      {/* Search & Filter Toolbar */}
      <form className="admin-v3-toolbar" method="GET">
        <div className="admin-v3-search-wrap">
          <span className="admin-v3-search-icon">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <circle cx="11" cy="11" r="8" />
              <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
          </span>
          <input
            name="q"
            defaultValue={q ?? ""}
            placeholder="Search by product name, slug, or SKU…"
            className="admin-v3-search-input"
          />
        </div>

        <select name="category" defaultValue={category ?? ""} className="admin-v3-cat-select">
          <option value="">All Categories</option>
          {allCategories.map((c) => (
            <option key={c.id} value={c.slug}>
              {c.name}
            </option>
          ))}
        </select>

        <button type="submit" className="admin-v3-btn-filter">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
          </svg>
          <span>Filter</span>
        </button>

        <Link href="/admin/categories" className="admin-v3-btn-categories">
          Manage Categories
        </Link>

        <Link href="/admin/products/new" className="admin-v3-btn-add">
          <span>+ Add New Product</span>
        </Link>

        <div style={{ marginLeft: "auto" }}>
          <AdminCatalogControls />
        </div>
      </form>

      {/* Products Table Card */}
      <div className="admin-v3-table-card">
        <div style={{ overflowX: "auto" }}>
          <table className="admin-v3-table">
            <thead>
              <tr>
                <th style={{ width: 44, textAlign: "center" }}>
                  <input type="checkbox" className="admin-v3-checkbox" aria-label="Select all products" />
                </th>
                <th style={{ minWidth: 230 }}>PRODUCT</th>
                <th>CATEGORY</th>
                <th>PACK SIZES</th>
                <th>PRICE RANGE</th>
                <th>STOCK UNITS</th>
                <th>STATUS</th>
                <th style={{ textAlign: "right", minWidth: 170 }}>ACTIONS</th>
              </tr>
            </thead>
            <tbody>
              {filtered.length === 0 ? (
                <tr>
                  <td colSpan={8} style={{ textAlign: "center", padding: "48px 20px", color: "#7C7265" }}>
                    No products found matching your search.
                  </td>
                </tr>
              ) : (
                filtered.map((p) => {
                  const totalStock = p.variants.reduce((sum, v) => sum + v.stock, 0);
                  const isLow = p.variants.some((v) => v.stock <= v.lowStockThreshold && v.stock > 0);
                  const minPrice = p.variants.length > 0 ? Math.min(...p.variants.map((v) => v.pricePaise)) : 0;
                  const maxPrice = p.variants.length > 0 ? Math.max(...p.variants.map((v) => v.pricePaise)) : 0;

                  // Determine status pill
                  let statusClass = "admin-v3-status-active";
                  let statusLabel = "ACTIVE";
                  let statusDotColor = "#2E7D32";

                  if (!p.active) {
                    statusClass = "admin-v3-status-archived";
                    statusLabel = "ARCHIVED";
                    statusDotColor = "#7A7264";
                  } else if (totalStock === 0) {
                    statusClass = "admin-v3-status-out";
                    statusLabel = "OUT OF STOCK";
                    statusDotColor = "#A73A2A";
                  } else if (isLow) {
                    statusClass = "admin-v3-status-low";
                    statusLabel = "LOW STOCK";
                    statusDotColor = "#B4691B";
                  }

                  // Determine stock pill
                  let stockClass = "admin-v3-stock-in";
                  let stockText = `${totalStock} IN STOCK`;
                  if (totalStock === 0) {
                    stockClass = "admin-v3-stock-out";
                    stockText = "OUT OF STOCK";
                  } else if (isLow) {
                    stockClass = "admin-v3-stock-low";
                    stockText = `${totalStock} IN STOCK`;
                  }

                  return (
                    <tr key={p.id}>
                      {/* Checkbox */}
                      <td style={{ textAlign: "center" }}>
                        <input type="checkbox" className="admin-v3-checkbox" aria-label={`Select ${p.name}`} />
                      </td>

                      {/* Product Thumbnail & Name */}
                      <td>
                        <div className="admin-v3-product-cell">
                          {/* eslint-disable-next-line @next/next/no-img-element */}
                          <img
                            src={p.imageUrl || "/assets/shop-cashews.jpg"}
                            alt={p.name}
                            className="admin-v3-product-thumb"
                          />
                          <div>
                            <span className="admin-v3-product-name">{p.name}</span>
                            <span className="admin-v3-product-slug">/{p.slug}</span>
                          </div>
                        </div>
                      </td>

                      {/* Category */}
                      <td>
                        <span className="admin-v3-cat-pill">
                          {p.category?.name ? p.category.name.toUpperCase() : "GENERAL"}
                        </span>
                      </td>

                      {/* Pack Sizes */}
                      <td>
                        <div className="admin-v3-sizes-row">
                          {p.variants.map((v) => (
                            <span key={v.id} className="admin-v3-size-pill">
                              {v.label}
                            </span>
                          ))}
                          {p.variants.length === 0 && (
                            <span style={{ fontSize: 11, color: "#8C8477" }}>—</span>
                          )}
                        </div>
                      </td>

                      {/* Price Range */}
                      <td>
                        <span className="admin-v3-price-range">
                          {minPrice === maxPrice
                            ? formatINR(minPrice)
                            : `${formatINR(minPrice)} – ${formatINR(maxPrice)}`}
                        </span>
                      </td>

                      {/* Stock Units */}
                      <td>
                        <span className={`admin-v3-stock-pill ${stockClass}`}>
                          {stockText}
                        </span>
                      </td>

                      {/* Status */}
                      <td>
                        <span className={`admin-v3-status-pill ${statusClass}`}>
                          <span className="admin-v3-status-dot" style={{ background: statusDotColor }} />
                          <span>{statusLabel}</span>
                        </span>
                      </td>

                      {/* Actions */}
                      <td>
                        <AdminProductRowActions productId={p.id} productName={p.name} slug={p.slug} />
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>

        {/* Table Footer / Pagination */}
        <div className="admin-v3-table-footer">
          <span>
            Showing 1–{filtered.length} of {totalProducts} products
          </span>

          <div className="admin-v3-pagination">
            <button type="button" className="admin-v3-page-btn" aria-label="Previous page">
              ‹
            </button>
            <button type="button" className="admin-v3-page-btn admin-v3-page-active">
              1
            </button>
            <button type="button" className="admin-v3-page-btn" aria-label="Next page">
              ›
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
