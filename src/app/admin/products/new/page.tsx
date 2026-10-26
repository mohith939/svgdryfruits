"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { ImageUploader } from "@/components/ImageUploader";
import { MultiImageUploader } from "@/components/MultiImageUploader";
import { REAL_PRODUCTS } from "@/lib/catalog-data";

type Category = { id: string; name: string; slug: string };

type VariantRow = {
  label: string;
  sku: string;
  priceINR: number | string;
  compareINR: number | string;
  stock: number | string;
  lowStockThreshold: number | string;
  active: boolean;
};

export default function NewProductPage() {
  const router = useRouter();
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [form, setForm] = useState({
    name: "",
    slug: "",
    description: "",
    categoryId: "",
    imageUrl: "/assets/mixed-nuts-reference.jpg",
    images: ["/assets/mixed-nuts-reference.jpg"],
    isCombo: false,
    active: true,
  });

  // Default pack sizes for Indian dry fruits brand
  const [variants, setVariants] = useState<VariantRow[]>([
    { label: "250 g", sku: "", priceINR: 299, compareINR: "", stock: 25, lowStockThreshold: 5, active: true },
    { label: "500 g", sku: "", priceINR: 569, compareINR: "", stock: 15, lowStockThreshold: 5, active: true },
    { label: "1 kg", sku: "", priceINR: 1099, compareINR: "", stock: 10, lowStockThreshold: 3, active: true },
  ]);

  useEffect(() => {
    // Fetch categories directly from admin categories endpoint
    fetch("/api/admin/categories")
      .then((r) => r.json())
      .then((d) => {
        if (d.categories) {
          setCategories(d.categories);
        }
      })
      .catch(() => {});
  }, []);

  function handleNameChange(e: React.ChangeEvent<HTMLInputElement>) {
    const val = e.target.value;
    const autoSlug = val.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");
    setForm((f) => ({
      ...f,
      name: val,
      slug: f.slug === "" || f.slug === autoSlug.slice(0, -1) ? autoSlug : f.slug,
    }));
  }

  function updateVariant(idx: number, field: keyof VariantRow, val: unknown) {
    setVariants((prev) => {
      const copy = [...prev];
      copy[idx] = { ...copy[idx], [field]: val };
      return copy;
    });
  }

  function removeVariant(idx: number) {
    if (variants.length <= 1) {
      alert("At least one pack size variant is required.");
      return;
    }
    setVariants((prev) => prev.filter((_, i) => i !== idx));
  }

  function addVariantPreset(label: string, defaultPrice: number = 399) {
    setVariants((prev) => [
      ...prev,
      {
        label,
        sku: "",
        priceINR: defaultPrice,
        compareINR: "",
        stock: 20,
        lowStockThreshold: 5,
        active: true,
      },
    ]);
  }

  function handleSelectPreset(slug: string) {
    if (!slug) return;
    const p = REAL_PRODUCTS.find((item) => item.slug === slug);
    if (!p) return;
    const matchedCat = categories.find((c) => c.slug === p.categorySlug);
    setForm((prev) => ({
      ...prev,
      name: p.name,
      slug: p.slug,
      description: p.description,
      categoryId: matchedCat ? matchedCat.id : prev.categoryId,
      imageUrl: p.imageUrl,
      images: [p.imageUrl],
      isCombo: p.isCombo,
      active: true,
    }));
    setVariants(
      p.variants.map((v) => ({
        label: v.label,
        sku: v.sku,
        priceINR: Math.round(v.pricePaise / 100),
        compareINR: v.comparePricePaise ? Math.round(v.comparePricePaise / 100) : "",
        stock: v.stock,
        lowStockThreshold: 5,
        active: true,
      }))
    );
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);

    if (!form.name.trim()) {
      setError("Product name is required.");
      return;
    }
    if (!form.slug.trim()) {
      setError("Product slug is required.");
      return;
    }

    setLoading(true);

    try {
      const formattedVariants = variants
        .filter((v) => v.label.trim() && Number(v.priceINR) > 0)
        .map((v) => ({
          label: v.label.trim(),
          sku: v.sku.trim() || `${form.slug.toUpperCase()}-${v.label.replace(/\s+/g, "")}`,
          pricePaise: Math.round(Number(v.priceINR) * 100),
          comparePricePaise: v.compareINR ? Math.round(Number(v.compareINR) * 100) : null,
          stock: Number(v.stock) || 0,
          lowStockThreshold: Number(v.lowStockThreshold) || 5,
          active: v.active,
        }));

      if (formattedVariants.length === 0) {
        setError("Please define at least one valid pack size with a price > 0.");
        setLoading(false);
        return;
      }

      const res = await fetch("/api/admin/products", {
        method: "POST",
        headers: { "content-type": "application/json" },
        body: JSON.stringify({
          name: form.name.trim(),
          slug: form.slug.trim(),
          description: form.description.trim() || undefined,
          categoryId: form.categoryId || undefined,
          imageUrl: form.images[0] || form.imageUrl.trim() || undefined,
          images: form.images.length > 0 ? form.images : form.imageUrl ? [form.imageUrl] : [],
          isCombo: form.isCombo,
          active: form.active,
          variants: formattedVariants,
        }),
      });

      const data = await res.json();
      if (!res.ok) {
        setError(data.error || "Failed to create product");
        setLoading(false);
      } else {
        router.push("/admin/products");
        router.refresh();
      }
    } catch {
      setError("An unexpected error occurred. Please try again.");
      setLoading(false);
    }
  }

  return (
    <div className="admin-content">
      <div className="admin-page-header">
        <div>
          <div className="breadcrumbs">
            <Link href="/admin">Dashboard</Link>
            <span aria-hidden="true">/</span>
            <Link href="/admin/products">Products</Link>
            <span aria-hidden="true">/</span>
            <span>New</span>
          </div>
          <h1>Create New <em>Product</em></h1>
          <p>Add a product with high-definition image upload and standard weight pack sizes.</p>
        </div>
      </div>

      {error && <div className="admin-error-banner" role="alert">{error}</div>}

      <form className="admin-form" onSubmit={handleSubmit}>
        <div className="admin-card">
          <h2>1. Basic Information &amp; Image</h2>

          <div style={{ background: "#F6F5F2", border: "1px dashed #D6C8B5", borderRadius: 8, padding: "12px 16px", marginBottom: 20 }}>
            <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", flexWrap: "wrap", gap: 10 }}>
              <div>
                <strong style={{ fontSize: 13, color: "#0E3626" }}>⚡ Quick-Fill with Realistic Dry Fruit Preset</strong>
                <p style={{ margin: 0, fontSize: 12, color: "#7C7265" }}>
                  Optionally pre-fill authentic product title, description, pack sizes (250g, 500g, 1kg), INR prices, and HD photo.
                </p>
              </div>
              <select
                onChange={(e) => handleSelectPreset(e.target.value)}
                defaultValue=""
                style={{ padding: "8px 12px", borderRadius: 6, border: "1px solid #D6C8B5", background: "#FFFFFF", fontSize: 13, fontWeight: 500 }}
              >
                <option value="">— Choose a Dry Fruit Preset —</option>
                {REAL_PRODUCTS.map((p) => (
                  <option key={p.slug} value={p.slug}>
                    {p.name}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div className="form-group-row">
            <div className="form-group">
              <label htmlFor="pname">Product Name *</label>
              <input id="pname" required value={form.name} onChange={handleNameChange} placeholder="e.g. Premium California Almonds" />
            </div>
            <div className="form-group">
              <label htmlFor="pslug">URL Slug *</label>
              <input id="pslug" required value={form.slug} onChange={(e) => setForm((f) => ({ ...f, slug: e.target.value }))} placeholder="california-almonds" />
            </div>
          </div>

          <div className="form-group">
            <label htmlFor="pdesc">Description</label>
            <textarea id="pdesc" rows={3} value={form.description} onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))} placeholder="Rich, crunchy whole almonds packed with natural nutrients." />
          </div>

          <div className="form-group-row">
            <div className="form-group">
              <label htmlFor="pcat">Category</label>
              <select id="pcat" value={form.categoryId} onChange={(e) => setForm((f) => ({ ...f, categoryId: e.target.value }))}>
                <option value="">— Select Category —</option>
                {categories.map((c) => (
                  <option key={c.id} value={c.id}>{c.name}</option>
                ))}
              </select>
            </div>
            <div className="form-group" style={{ display: "flex", alignItems: "flex-end", gap: 16 }}>
              <label className="checkbox-label" style={{ marginBottom: 12 }}>
                <input type="checkbox" checked={form.isCombo} onChange={(e) => setForm((f) => ({ ...f, isCombo: e.target.checked }))} />
                <span>Gift Hamper / Combo</span>
              </label>
              <label className="checkbox-label" style={{ marginBottom: 12 }}>
                <input type="checkbox" checked={form.active} onChange={(e) => setForm((f) => ({ ...f, active: e.target.checked }))} />
                <span>Active on Storefront</span>
              </label>
            </div>
          </div>

          {/* Integrated Multi-Image Gallery Uploader */}
          <MultiImageUploader
            images={form.images}
            onChange={(imgs) => setForm((f) => ({ ...f, images: imgs, imageUrl: imgs[0] || "" }))}
            label="Product Photos &amp; Gallery (Add multiple images)"
          />
        </div>

        <div className="admin-card">
          <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 12, flexWrap: "wrap", gap: 8 }}>
            <div>
              <h2 style={{ margin: 0 }}>2. Weight Pack Sizes &amp; Pricing</h2>
              <p className="admin-card-subtitle" style={{ margin: "4px 0 0" }}>
                Dry fruit pack sizes (e.g., 250 g, 500 g, 1 kg). Each variant maintains its own inventory stock.
              </p>
            </div>
            <div style={{ display: "flex", gap: 6 }}>
              <button
                type="button"
                onClick={() => addVariantPreset("250 g", 299)}
                className="button button-outline"
                style={{ fontSize: 12, padding: "4px 8px" }}
              >
                + 250 g
              </button>
              <button
                type="button"
                onClick={() => addVariantPreset("500 g", 569)}
                className="button button-outline"
                style={{ fontSize: 12, padding: "4px 8px" }}
              >
                + 500 g
              </button>
              <button
                type="button"
                onClick={() => addVariantPreset("1 kg", 1099)}
                className="button button-outline"
                style={{ fontSize: 12, padding: "4px 8px" }}
              >
                + 1 kg
              </button>
              <button
                type="button"
                onClick={() => addVariantPreset("Custom Size", 499)}
                className="button button-outline"
                style={{ fontSize: 12, padding: "4px 8px" }}
              >
                + Custom
              </button>
            </div>
          </div>

          <div className="admin-table-wrap">
            <table className="admin-table">
              <thead>
                <tr>
                  <th style={{ width: 140 }}>Pack Size</th>
                  <th>SKU (auto-generated if empty)</th>
                  <th style={{ width: 120 }}>Price (₹) *</th>
                  <th style={{ width: 120 }}>MRP / Compare (₹)</th>
                  <th style={{ width: 100 }}>Stock Units</th>
                  <th style={{ width: 70, textAlign: "center" }}>Active</th>
                  <th style={{ width: 50 }}></th>
                </tr>
              </thead>
              <tbody>
                {variants.map((v, i) => (
                  <tr key={i}>
                    <td>
                      <input
                        className="table-input"
                        value={v.label}
                        onChange={(e) => updateVariant(i, "label", e.target.value)}
                        placeholder="250 g"
                        required
                      />
                    </td>
                    <td>
                      <input
                        className="table-input"
                        value={v.sku}
                        placeholder="Optional SKU"
                        onChange={(e) => updateVariant(i, "sku", e.target.value)}
                      />
                    </td>
                    <td>
                      <input
                        className="table-input num-input"
                        type="number"
                        min="1"
                        required
                        value={v.priceINR}
                        onChange={(e) => updateVariant(i, "priceINR", e.target.value)}
                      />
                    </td>
                    <td>
                      <input
                        className="table-input num-input"
                        type="number"
                        min="0"
                        value={v.compareINR}
                        placeholder="Optional"
                        onChange={(e) => updateVariant(i, "compareINR", e.target.value)}
                      />
                    </td>
                    <td>
                      <input
                        className="table-input num-input"
                        type="number"
                        min="0"
                        value={v.stock}
                        onChange={(e) => updateVariant(i, "stock", e.target.value)}
                      />
                    </td>
                    <td style={{ textAlign: "center" }}>
                      <input
                        type="checkbox"
                        checked={v.active}
                        onChange={(e) => updateVariant(i, "active", e.target.checked)}
                      />
                    </td>
                    <td>
                      <button
                        type="button"
                        onClick={() => removeVariant(i)}
                        title="Remove variant"
                        style={{
                          background: "none",
                          border: "none",
                          color: "#C45B28",
                          cursor: "pointer",
                          fontSize: 16,
                          fontWeight: "bold",
                        }}
                      >
                        ×
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        <div className="admin-form-actions">
          <Link href="/admin/products" className="button button-outline">Cancel</Link>
          <button type="submit" className="button button-primary" disabled={loading}>
            {loading ? "Creating…" : "Save Product"}
          </button>
        </div>
      </form>
    </div>
  );
}
