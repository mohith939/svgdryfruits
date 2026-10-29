"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { ImageUploader } from "@/components/ImageUploader";

interface CategoryItem {
  id: string;
  slug: string;
  name: string;
  description: string | null;
  imageUrl: string | null;
  sortOrder: number;
  active: boolean;
  productCount: number;
}

const CATEGORY_ICON_PRESETS = [
  { label: "Nuts Icon", url: "/assets/cat-hd-nuts.png" },
  { label: "Dried Fruits Icon", url: "/assets/cat-hd-dried-fruits.png" },
  { label: "Seeds Icon", url: "/assets/cat-hd-seeds.png" },
  { label: "Healthy Mix Icon", url: "/assets/cat-hd-healthy-mix.png" },
  { label: "Combos Icon", url: "/assets/cat-hd-gift-boxes.png" },
  { label: "All Products Icon", url: "/assets/cat-hd-all-products.png" },
];

export default function AdminCategoriesPage() {
  const [categories, setCategories] = useState<CategoryItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  // New category form
  const [name, setName] = useState("");
  const [slug, setSlug] = useState("");
  const [description, setDescription] = useState("");
  const [imageUrl, setImageUrl] = useState<string | null>("/assets/cat-hd-nuts.png");
  const [sortOrder, setSortOrder] = useState("1");
  const [creating, setCreating] = useState(false);

  // Editing state
  const [editingId, setEditingId] = useState<string | null>(null);
  const [editName, setEditName] = useState("");
  const [editSlug, setEditSlug] = useState("");
  const [editDesc, setEditDesc] = useState("");
  const [editImg, setEditImg] = useState<string | null>(null);
  const [editSort, setEditSort] = useState("0");

  useEffect(() => {
    loadCategories();
  }, []);

  function loadCategories() {
    setLoading(true);
    fetch("/api/admin/categories")
      .then((r) => r.json())
      .then((d) => {
        if (d.categories) setCategories(d.categories);
        setLoading(false);
      })
      .catch(() => setLoading(false));
  }

  function handleNameChange(val: string) {
    setName(val);
    const auto = val.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");
    if (!slug || slug === auto.slice(0, -1)) {
      setSlug(auto);
    }
  }

  async function handleCreate(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setSuccess(null);
    setCreating(true);

    try {
      const res = await fetch("/api/admin/categories", {
        method: "POST",
        headers: { "content-type": "application/json" },
        body: JSON.stringify({
          name: name.trim(),
          slug: slug.trim(),
          description: description.trim() || undefined,
          imageUrl: imageUrl || undefined,
          sortOrder: Number(sortOrder) || 0,
          active: true,
        }),
      });

      const data = await res.json();
      if (!res.ok) {
        setError(data.error || "Failed to create category");
      } else {
        setSuccess(`Category "${name}" created successfully!`);
        setName("");
        setSlug("");
        setDescription("");
        setImageUrl("/assets/cat-hd-nuts.png");
        setSortOrder("1");
        loadCategories();
      }
    } catch {
      setError("An unexpected error occurred.");
    } finally {
      setCreating(false);
    }
  }

  function startEdit(c: CategoryItem) {
    setEditingId(c.id);
    setEditName(c.name);
    setEditSlug(c.slug);
    setEditDesc(c.description || "");
    setEditImg(c.imageUrl);
    setEditSort(String(c.sortOrder));
    setError(null);
    setSuccess(null);
  }

  async function handleSaveEdit(id: string) {
    try {
      const res = await fetch("/api/admin/categories", {
        method: "PUT",
        headers: { "content-type": "application/json" },
        body: JSON.stringify({
          id,
          name: editName.trim(),
          slug: editSlug.trim(),
          description: editDesc.trim() || null,
          imageUrl: editImg || null,
          sortOrder: Number(editSort) || 0,
        }),
      });

      if (res.ok) {
        setEditingId(null);
        setSuccess("Category updated successfully!");
        loadCategories();
      } else {
        const d = await res.json();
        setError(d.error || "Failed to update category");
      }
    } catch {
      setError("Error updating category");
    }
  }

  async function handleToggleActive(c: CategoryItem) {
    await fetch("/api/admin/categories", {
      method: "PUT",
      headers: { "content-type": "application/json" },
      body: JSON.stringify({ id: c.id, active: !c.active }),
    });
    loadCategories();
  }

  async function handleDelete(c: CategoryItem) {
    if (!confirm(`Are you sure you want to delete category "${c.name}"? Products in this category will remain, but will be uncategorized.`)) {
      return;
    }
    const res = await fetch(`/api/admin/categories?id=${c.id}`, { method: "DELETE" });
    if (res.ok) {
      setSuccess(`Deleted category "${c.name}".`);
      loadCategories();
    } else {
      setError("Failed to delete category");
    }
  }

  return (
    <div className="admin-content">
      <div className="admin-page-header">
        <div>
          <div className="breadcrumbs">
            <Link href="/admin">Dashboard</Link>
            <span aria-hidden="true">/</span>
            <span>Categories</span>
          </div>
          <h1>Catalog <em>Categories</em></h1>
          <p>Create and customize product categories and icon images displayed in the storefront and filter bar.</p>
        </div>
      </div>

      {error && <div className="admin-error-banner" role="alert">{error}</div>}
      {success && <div className="admin-info-banner" role="status">{success}</div>}

      <div className="admin-two-cols">
        {/* Create Category Form */}
        <div className="admin-card">
          <h2>+ Add New Category</h2>
          <form className="admin-form" onSubmit={handleCreate}>
            <div className="form-group-row">
              <div className="form-group">
                <label htmlFor="cat-name">Category Name *</label>
                <input
                  id="cat-name"
                  required
                  placeholder="e.g. Organic Berries"
                  value={name}
                  onChange={(e) => handleNameChange(e.target.value)}
                />
              </div>

              <div className="form-group">
                <label htmlFor="cat-slug">URL Slug *</label>
                <input
                  id="cat-slug"
                  required
                  placeholder="organic-berries"
                  value={slug}
                  onChange={(e) => setSlug(e.target.value)}
                />
              </div>
            </div>

            <div className="form-group">
              <label htmlFor="cat-desc">Description (optional)</label>
              <textarea
                id="cat-desc"
                rows={2}
                placeholder="Short subtitle for the category"
                value={description}
                onChange={(e) => setDescription(e.target.value)}
              />
            </div>

            <div className="form-group">
              <ImageUploader
                value={imageUrl}
                onChange={(url) => setImageUrl(url)}
                label="Category Icon / Image (Displayed in Shop Tabs)"
                presetImages={CATEGORY_ICON_PRESETS}
              />
            </div>

            <div className="form-group" style={{ maxWidth: 180 }}>
              <label htmlFor="cat-sort">Display Sort Order</label>
              <input
                id="cat-sort"
                type="number"
                min="0"
                value={sortOrder}
                onChange={(e) => setSortOrder(e.target.value)}
              />
            </div>

            <button type="submit" className="button button-primary" disabled={creating}>
              {creating ? "Adding Category…" : "Add Category"}
            </button>
          </form>
        </div>

        {/* Categories List */}
        <div className="admin-card">
          <h2>All Categories ({categories.length})</h2>
          {loading ? (
            <p>Loading categories…</p>
          ) : categories.length === 0 ? (
            <p className="admin-empty-text">No categories found.</p>
          ) : (
            <div className="admin-table-wrap">
              <table className="admin-table">
                <thead>
                  <tr>
                    <th>Icon</th>
                    <th>Category</th>
                    <th>Products</th>
                    <th>Order</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {categories.map((c) => {
                    const isEditing = editingId === c.id;

                    if (isEditing) {
                      return (
                        <tr key={c.id} style={{ background: "#fdfbf7" }}>
                          <td colSpan={6}>
                            <div style={{ display: "flex", flexDirection: "column", gap: 12, padding: "8px 0" }}>
                              <strong>Edit Category: {c.name}</strong>
                              <div className="form-group-row">
                                <input
                                  value={editName}
                                  onChange={(e) => setEditName(e.target.value)}
                                  placeholder="Name"
                                />
                                <input
                                  value={editSlug}
                                  onChange={(e) => setEditSlug(e.target.value)}
                                  placeholder="Slug"
                                />
                                <input
                                  type="number"
                                  value={editSort}
                                  onChange={(e) => setEditSort(e.target.value)}
                                  style={{ width: 80 }}
                                  placeholder="Order"
                                />
                              </div>
                              <ImageUploader
                                value={editImg}
                                onChange={(url) => setEditImg(url)}
                                label="Update Icon"
                                presetImages={CATEGORY_ICON_PRESETS}
                              />
                              <div style={{ display: "flex", gap: 8 }}>
                                <button
                                  type="button"
                                  className="button button-primary"
                                  onClick={() => handleSaveEdit(c.id)}
                                >
                                  Save Changes
                                </button>
                                <button
                                  type="button"
                                  className="button button-outline"
                                  onClick={() => setEditingId(null)}
                                >
                                  Cancel
                                </button>
                              </div>
                            </div>
                          </td>
                        </tr>
                      );
                    }

                    return (
                      <tr key={c.id}>
                        <td>
                          {c.imageUrl ? (
                            /* eslint-disable-next-line @next/next/no-img-element */
                            <img
                              src={c.imageUrl}
                              alt=""
                              style={{ width: 44, height: 44, objectFit: "contain", borderRadius: 6, background: "#f6f3eb", padding: 2 }}
                            />
                          ) : (
                            <span style={{ fontSize: 11, color: "#888" }}>No icon</span>
                          )}
                        </td>
                        <td>
                          <strong>{c.name}</strong>
                          <div style={{ fontSize: 11, color: "#8A7E6F" }}>/{c.slug}</div>
                        </td>
                        <td>
                          <span className="badge badge-neutral">{c.productCount} products</span>
                        </td>
                        <td>{c.sortOrder}</td>
                        <td>
                          <button
                            type="button"
                            className={`badge-toggle ${c.active ? "badge-success" : "badge-danger"}`}
                            onClick={() => handleToggleActive(c)}
                            title="Click to toggle active state"
                          >
                            {c.active ? "Active" : "Hidden"}
                          </button>
                        </td>
                        <td>
                          <div style={{ display: "flex", gap: 10, alignItems: "center" }}>
                            <button
                              type="button"
                              className="admin-action-link"
                              style={{ background: "none", border: "none", cursor: "pointer", padding: 0 }}
                              onClick={() => startEdit(c)}
                            >
                              Edit
                            </button>
                            <button
                              type="button"
                              className="admin-remove-btn"
                              onClick={() => handleDelete(c)}
                              title="Delete category"
                            >
                              Delete
                            </button>
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
