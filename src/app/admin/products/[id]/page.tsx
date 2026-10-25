"use client";

import Link from "next/link";
import { useParams, useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { ImageUploader } from "@/components/ImageUploader";
import { MultiImageUploader } from "@/components/MultiImageUploader";

type Variant = {
  id: string;
  sku: string;
  label: string;
  pricePaise: number;
  comparePricePaise: number | null;
  stock: number;
  lowStockThreshold: number;
  active: boolean;
};

type Product = {
  id: string;
  name: string;
  slug: string;
  description: string | null;
  categoryId: string | null;
  imageUrl: string | null;
  images?: string[] | null;
  isCombo: boolean;
  active: boolean;
  variants: Variant[];
};

type Category = { id: string; name: string; slug: string };

export default function EditProductPage() {
  const params = useParams();
  const router = useRouter();
  const id = params?.id as string;

  const [product, setProduct] = useState<Product | null>(null);
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  // New variant form
  const [newVariant, setNewVariant] = useState({
    label: "250 g",
    sku: "",
    priceINR: "",
    compareINR: "",
    stock: "20",
    lowStockThreshold: "5",
  });

  useEffect(() => {
    if (!id) return;

    Promise.all([
      fetch(`/api/admin/products/${id}`).then((r) => r.json()),
      fetch("/api/admin/categories").then((r) => r.json()),
    ])
      .then(([pData, catData]) => {
        if (pData.product) {
          const p = pData.product;
          const initialImgs = p.images && p.images.length > 0 ? p.images : (p.imageUrl ? [p.imageUrl] : []);
          setProduct({ ...p, images: initialImgs });
        } else {
          setError("Product not found");
        }
        if (catData.categories) {
          setCategories(catData.categories);
        }
        setLoading(false);
      })
      .catch(() => {
        setError("Failed to load product details");
        setLoading(false);
      });
  }, [id]);

  async function handleProductSave(e: React.FormEvent) {
    e.preventDefault();
    if (!product) return;
    setSaving(true);
    setError(null);
    setSuccess(null);

    const mainImg = product.images && product.images.length > 0 ? product.images[0] : product.imageUrl;

    try {
      const res = await fetch(`/api/admin/products/${id}`, {
        method: "PUT",
        headers: { "content-type": "application/json" },
        body: JSON.stringify({
          name: product.name,
          slug: product.slug,
          description: product.description,
          categoryId: product.categoryId || null,
          imageUrl: mainImg,
          images: product.images || (mainImg ? [mainImg] : []),
          isCombo: product.isCombo,
          active: product.active,
        }),
      });

      const data = await res.json();
      if (!res.ok) {
        setError(data.error || "Update failed");
      } else {
        setSuccess("Product information updated successfully.");
      }
    } catch {
      setError("An unexpected error occurred.");
    } finally {
      setSaving(false);
    }
  }

  async function handleVariantUpdate(v: Variant, field: string, value: unknown) {
    try {
      const payload: Record<string, unknown> = { [field]: value };
      const res = await fetch(`/api/admin/variants/${v.id}`, {
        method: "PUT",
        headers: { "content-type": "application/json" },
        body: JSON.stringify(payload),
      });

      if (res.ok) {
        setProduct((prev) => {
          if (!prev) return null;
          return {
            ...prev,
            variants: prev.variants.map((item) =>
              item.id === v.id ? { ...item, [field]: value } : item
            ),
          };
        });
      }
    } catch (err) {
      console.error(err);
    }
  }

  async function handleAddVariant(e: React.FormEvent) {
    e.preventDefault();
    if (!product || !newVariant.priceINR) return;
    setError(null);

    try {
      const res = await fetch("/api/admin/variants", {
        method: "POST",
        headers: { "content-type": "application/json" },
        body: JSON.stringify({
          productId: product.id,
          label: newVariant.label.trim(),
          sku:
            newVariant.sku.trim() ||
            `${product.slug.toUpperCase()}-${newVariant.label.replace(/\s+/g, "")}`,
          pricePaise: Math.round(Number(newVariant.priceINR) * 100),
          comparePricePaise: newVariant.compareINR
            ? Math.round(Number(newVariant.compareINR) * 100)
            : null,
          stock: Number(newVariant.stock) || 0,
          lowStockThreshold: Number(newVariant.lowStockThreshold) || 5,
          active: true,
        }),
      });

      const data = await res.json();
      if (!res.ok) {
        setError(data.error || "Could not add variant");
      } else {
        setProduct((prev) =>
          prev ? { ...prev, variants: [...prev.variants, data.variant] } : null
        );
        setNewVariant({
          label: "500 g",
          sku: "",
          priceINR: "",
          compareINR: "",
          stock: "20",
          lowStockThreshold: "5",
        });
      }
    } catch {
      setError("Error adding variant");
    }
  }

  if (loading)
    return (
      <div className="admin-content">
        <p>Loading product…</p>
      </div>
    );
  if (!product)
    return (
      <div className="admin-content">
        <p>Product not found.</p>
      </div>
    );

  return (
    <div className="admin-content">
      <div className="admin-page-header">
        <div>
          <div className="breadcrumbs">
            <Link href="/admin">Dashboard</Link>
            <span aria-hidden="true">/</span>
            <Link href="/admin/products">Products</Link>
            <span aria-hidden="true">/</span>
            <span>{product.name}</span>
          </div>
          <h1>
            Edit <em>{product.name}</em>
          </h1>
          <p>Update product photo, details, pack sizes, selling prices, and stock inventory.</p>
        </div>
        <div style={{ display: "flex", gap: 10 }}>
          <Link
            href={`/shop/${product.slug}`}
            target="_blank"
            className="button button-outline"
            style={{ background: "#fff", color: "#073D26", borderColor: "#c4baa8" }}
          >
            View on Storefront <span aria-hidden="true">↗</span>
          </Link>
        </div>
      </div>

      {error && (
        <div className="admin-error-banner" role="alert">
          {error}
        </div>
      )}
      {success && (
        <div className="admin-success-banner" role="status">
          {success}
        </div>
      )}

      <form className="admin-form" onSubmit={handleProductSave}>
        <div className="admin-card">
          <h2>1. Product Details &amp; Image</h2>
          <div className="form-group-row">
            <div className="form-group">
              <label htmlFor="pname">Product Name *</label>
              <input
                id="pname"
                required
                value={product.name}
                onChange={(e) => setProduct({ ...product, name: e.target.value })}
              />
            </div>
            <div className="form-group">
              <label htmlFor="pslug">URL Slug *</label>
              <input
                id="pslug"
                required
                value={product.slug}
                onChange={(e) => setProduct({ ...product, slug: e.target.value })}
              />
            </div>
          </div>

          <div className="form-group">
            <label htmlFor="pdesc">Description</label>
            <textarea
              id="pdesc"
              rows={3}
              value={product.description ?? ""}
              onChange={(e) => setProduct({ ...product, description: e.target.value })}
            />
          </div>

          <div className="form-group-row">
            <div className="form-group">
              <label htmlFor="pcat">Category</label>
              <select
                id="pcat"
                value={product.categoryId ?? ""}
                onChange={(e) =>
                  setProduct({ ...product, categoryId: e.target.value || null })
                }
              >
                <option value="">— No Category —</option>
                {categories.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name}
                  </option>
                ))}
              </select>
            </div>
            <div className="form-group" style={{ display: "flex", alignItems: "flex-end", gap: 16 }}>
              <label className="checkbox-label" style={{ marginBottom: 12 }}>
                <input
                  type="checkbox"
                  checked={product.isCombo}
                  onChange={(e) => setProduct({ ...product, isCombo: e.target.checked })}
                />
                <span>Is Combo / Gifting Hamper</span>
              </label>
              <label className="checkbox-label" style={{ marginBottom: 12 }}>
                <input
                  type="checkbox"
                  checked={product.active}
                  onChange={(e) => setProduct({ ...product, active: e.target.checked })}
                />
                <span>Visible in Storefront (Active)</span>
              </label>
            </div>
          </div>

          {/* Integrated Multi-Image Gallery Uploader */}
          <MultiImageUploader
            images={product.images || []}
            onChange={(imgs) =>
              setProduct({
                ...product,
                images: imgs,
                imageUrl: imgs[0] || "",
              })
            }
            label="Product Photos &amp; Gallery (Add multiple images)"
          />

          <div className="admin-form-actions" style={{ marginTop: 20 }}>
            <button type="submit" className="button button-primary" disabled={saving}>
              {saving ? "Saving…" : "Save Product Details"}
            </button>
          </div>
        </div>
      </form>

      {/* Existing Variants List */}
      <div className="admin-card">
        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 12 }}>
          <div>
            <h2 style={{ margin: 0 }}>2. Pack Sizes &amp; Pricing</h2>
            <p className="admin-card-subtitle" style={{ margin: "4px 0 0" }}>
              Edit selling prices (₹) and stock quantities. Changes save automatically when you click outside an input.
            </p>
          </div>
        </div>

        <div className="admin-table-wrap">
          <table className="admin-table">
            <thead>
              <tr>
                <th style={{ width: 140 }}>Size Label</th>
                <th>SKU</th>
                <th style={{ width: 130 }}>Selling Price (₹)</th>
                <th style={{ width: 120 }}>Stock Units</th>
                <th style={{ width: 120 }}>Low Stock Alert</th>
                <th style={{ width: 100, textAlign: "center" }}>Active</th>
              </tr>
            </thead>
            <tbody>
              {product.variants.map((v) => (
                <tr key={v.id}>
                  <td>
                    <strong>{v.label}</strong>
                  </td>
                  <td>
                    <code>{v.sku}</code>
                  </td>
                  <td>
                    <div style={{ display: "flex", alignItems: "center", gap: 4 }}>
                      <span style={{ fontSize: 13, color: "#666" }}>₹</span>
                      <input
                        className="table-input num-input"
                        type="number"
                        min="1"
                        defaultValue={v.pricePaise / 100}
                        onBlur={(e) =>
                          handleVariantUpdate(
                            v,
                            "pricePaise",
                            Math.round(Number(e.target.value) * 100)
                          )
                        }
                      />
                    </div>
                  </td>
                  <td>
                    <input
                      className="table-input num-input"
                      type="number"
                      min="0"
                      defaultValue={v.stock}
                      onBlur={(e) =>
                        handleVariantUpdate(v, "stock", Number(e.target.value))
                      }
                    />
                  </td>
                  <td>
                    <input
                      className="table-input num-input"
                      type="number"
                      min="0"
                      defaultValue={v.lowStockThreshold}
                      onBlur={(e) =>
                        handleVariantUpdate(
                          v,
                          "lowStockThreshold",
                          Number(e.target.value)
                        )
                      }
                    />
                  </td>
                  <td style={{ textAlign: "center" }}>
                    <input
                      type="checkbox"
                      checked={v.active}
                      onChange={(e) =>
                        handleVariantUpdate(v, "active", e.target.checked)
                      }
                    />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {/* Add new variant */}
        <form className="add-variant-box" onSubmit={handleAddVariant} style={{ marginTop: 20 }}>
          <h3 style={{ fontSize: 14, fontWeight: 700, marginBottom: 10 }}>+ Add another pack size variant</h3>
          <div className="add-variant-row">
            <div className="form-group">
              <label>Pack Size</label>
              <input
                value={newVariant.label}
                onChange={(e) =>
                  setNewVariant({ ...newVariant, label: e.target.value })
                }
                placeholder="e.g. 250 g or 1 kg"
              />
            </div>
            <div className="form-group">
              <label>SKU (optional)</label>
              <input
                value={newVariant.sku}
                onChange={(e) =>
                  setNewVariant({ ...newVariant, sku: e.target.value })
                }
                placeholder="Auto-generated if empty"
              />
            </div>
            <div className="form-group">
              <label>Price (₹) *</label>
              <input
                type="number"
                required
                min="1"
                value={newVariant.priceINR}
                onChange={(e) =>
                  setNewVariant({ ...newVariant, priceINR: e.target.value })
                }
                placeholder="299"
              />
            </div>
            <div className="form-group">
              <label>Stock Count</label>
              <input
                type="number"
                min="0"
                value={newVariant.stock}
                onChange={(e) =>
                  setNewVariant({ ...newVariant, stock: e.target.value })
                }
                placeholder="20"
              />
            </div>
            <button
              type="submit"
              className="button button-outline add-variant-btn"
              style={{ background: "#073D26", color: "#fff", borderColor: "#073D26" }}
            >
              Add Pack Size
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
