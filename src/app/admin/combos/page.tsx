"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { formatINR } from "@/lib/money";

type ComponentItem = {
  id: string;
  quantity: number;
  componentVariantId: string;
  componentSku: string;
  componentLabel: string;
  componentStock: number;
  componentProductName: string;
};

type ComboVariantGroup = {
  variant: {
    id: string;
    sku: string;
    label: string;
    pricePaise: number;
    stock: number;
  };
  components: ComponentItem[];
};

type ComboProduct = {
  product: {
    id: string;
    name: string;
    slug: string;
    imageUrl: string | null;
  };
  variants: ComboVariantGroup[];
};

type AvailableComponent = {
  variant: {
    id: string;
    sku: string;
    label: string;
    stock: number;
  };
  productName: string;
};

export default function AdminCombosPage() {
  const [combos, setCombos] = useState<ComboProduct[]>([]);
  const [available, setAvailable] = useState<AvailableComponent[]>([]);
  const [loading, setLoading] = useState(true);
  const [addingVariantId, setAddingVariantId] = useState<string | null>(null);
  const [selectedCompId, setSelectedCompId] = useState("");
  const [qty, setQty] = useState("1");
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  useEffect(() => {
    loadCombos();
  }, []);

  function loadCombos() {
    setLoading(true);
    fetch("/api/admin/combos")
      .then((r) => r.json())
      .then((d) => {
        if (d.combos) setCombos(d.combos);
        if (d.availableComponents) setAvailable(d.availableComponents);
        setLoading(false);
      })
      .catch(() => setLoading(false));
  }

  async function handleAddComponent(variantId: string) {
    if (!selectedCompId) {
      setError("Please choose a dry fruit item from the dropdown.");
      return;
    }
    setError(null);
    setSuccess(null);

    const res = await fetch("/api/admin/combos", {
      method: "POST",
      headers: { "content-type": "application/json" },
      body: JSON.stringify({
        comboVariantId: variantId,
        componentVariantId: selectedCompId,
        quantity: Math.max(1, parseInt(qty, 10) || 1),
      }),
    });

    if (res.ok) {
      setSuccess("Added component to hamper successfully!");
      setAddingVariantId(null);
      setSelectedCompId("");
      setQty("1");
      loadCombos();
    } else {
      const err = await res.json();
      setError(err.error || "Failed to add component to hamper");
    }
  }

  async function handleRemoveComponent(id: string) {
    const confirmed = window.confirm("Remove this item from the hamper bundle?");
    if (!confirmed) return;

    const res = await fetch(`/api/admin/combos?id=${id}`, { method: "DELETE" });
    if (res.ok) {
      loadCombos();
    }
  }

  // Calculate how many complete hampers can be fulfilled right now
  function calculateMaxPackable(components: ComponentItem[]): number | null {
    if (!components || components.length === 0) return null;
    let minUnits = Infinity;
    for (const comp of components) {
      const count = Math.floor(comp.componentStock / (comp.quantity || 1));
      if (count < minUnits) minUnits = count;
    }
    return minUnits === Infinity ? 0 : minUnits;
  }

  return (
    <div className="admin-content">
      {/* Header */}
      <div className="admin-page-header">
        <div>
          <div className="breadcrumbs">
            <Link href="/admin">Dashboard</Link>
            <span aria-hidden="true">/</span>
            <span>Gift Combos &amp; Hampers</span>
          </div>
          <h1>Gift Combos &amp; <em>Hampers Builder</em></h1>
          <p>
            Bundle multiple dry fruits into luxury gift packs. Stock automatically syncs with component inventory.
          </p>
        </div>
        <Link href="/admin/products/new" className="button button-primary">
          + Create New Combo / Hamper
        </Link>
      </div>

      {error && <div className="admin-error-banner" role="alert">{error}</div>}
      {success && <div className="admin-success-banner" role="status">{success}</div>}

      {/* Educational Concept Guide */}
      <div
        className="admin-card"
        style={{
          background: "linear-gradient(135deg, #FAF7F2 0%, #F5EFE6 100%)",
          border: "1px solid #D6C7B2",
          borderRadius: 12,
          padding: "20px 24px",
          marginBottom: 24,
        }}
      >
        <div style={{ display: "flex", alignItems: "flex-start", gap: 14 }}>
          <div
            style={{
              fontSize: 28,
              lineHeight: 1,
              background: "#073D26",
              color: "#E2C376",
              padding: 10,
              borderRadius: 10,
            }}
          >
            🎁
          </div>
          <div style={{ flex: 1 }}>
            <h2 style={{ fontSize: 16, margin: "0 0 6px", color: "#073D26" }}>
              How SVG Gift Combos &amp; Hampers Work
            </h2>
            <p style={{ margin: "0 0 10px", fontSize: 13.5, color: "#4A4031", lineHeight: 1.5 }}>
              Combos (like <strong>Festive Gift Boxes</strong> or <strong>Wellness Packs</strong>) do not need separate inventory in your warehouse. Instead, you link individual dry fruits to each hamper.
            </p>
            <div
              style={{
                display: "grid",
                gridTemplateColumns: "repeat(auto-fit, minmax(220px, 1fr))",
                gap: 12,
                marginTop: 10,
              }}
            >
              <div
                style={{
                  background: "#fff",
                  padding: "10px 14px",
                  borderRadius: 8,
                  border: "1px solid #E4DCCF",
                  fontSize: 12.5,
                }}
              >
                <strong style={{ display: "block", color: "#073D26", marginBottom: 2 }}>
                  1. Bundle Products
                </strong>
                Add 1× Almonds 250g, 1× Cashews 250g, 1× Pistachios 250g to create a 3-in-1 Hamper.
              </div>
              <div
                style={{
                  background: "#fff",
                  padding: "10px 14px",
                  borderRadius: 8,
                  border: "1px solid #E4DCCF",
                  fontSize: 12.5,
                }}
              >
                <strong style={{ display: "block", color: "#073D26", marginBottom: 2 }}>
                  2. Automatic Deduction
                </strong>
                When an order arrives, the system automatically deducts stock from each component dry fruit.
              </div>
              <div
                style={{
                  background: "#fff",
                  padding: "10px 14px",
                  borderRadius: 8,
                  border: "1px solid #E4DCCF",
                  fontSize: 12.5,
                }}
              >
                <strong style={{ display: "block", color: "#073D26", marginBottom: 2 }}>
                  3. Live Fulfillable Count
                </strong>
                The &ldquo;Max Packable&rdquo; badge below shows how many complete gift boxes you can fulfill right now based on warehouse inventory.
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Combos list */}
      {loading ? (
        <div className="admin-card" style={{ textAlign: "center", padding: 32 }}>
          <p>Loading hampers &amp; combos…</p>
        </div>
      ) : combos.length === 0 ? (
        <div className="admin-card" style={{ textAlign: "center", padding: 40 }}>
          <h3 style={{ margin: "0 0 8px", color: "#073D26" }}>No combo gift boxes configured</h3>
          <p className="admin-muted" style={{ maxWidth: 460, margin: "0 auto 16px" }}>
            Create your first combo product (e.g., &ldquo;Royal Festive Dry Fruits Hamper&rdquo;) and select &ldquo;Is Combo / Gifting Hamper&rdquo;.
          </p>
          <Link href="/admin/products/new" className="button button-primary">
            + Create New Hamper
          </Link>
        </div>
      ) : (
        combos.map((c) => (
          <div key={c.product.id} className="admin-card" style={{ marginBottom: 24 }}>
            {/* Combo Header */}
            <div
              style={{
                display: "flex",
                alignItems: "center",
                justifyContent: "space-between",
                borderBottom: "1px solid #ECE7DE",
                paddingBottom: 14,
                marginBottom: 16,
                flexWrap: "wrap",
                gap: 12,
              }}
            >
              <div style={{ display: "flex", alignItems: "center", gap: 14 }}>
                {c.product.imageUrl && (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img
                    src={c.product.imageUrl}
                    alt={c.product.name}
                    style={{
                      width: 52,
                      height: 52,
                      borderRadius: 8,
                      objectFit: "cover",
                      border: "1px solid #DFD7CA",
                    }}
                  />
                )}
                <div>
                  <h2 style={{ margin: "0 0 2px", fontSize: 18, color: "#073D26" }}>
                    {c.product.name}
                  </h2>
                  <span style={{ fontSize: 12, color: "#776F62" }}>
                    /{c.product.slug} &bull; Gift Hamper Product
                  </span>
                </div>
              </div>
              <div style={{ display: "flex", gap: 8 }}>
                <Link
                  href={`/shop/${c.product.slug}`}
                  target="_blank"
                  className="button button-outline"
                  style={{ fontSize: 12, padding: "4px 10px", background: "#fff", color: "#073D26" }}
                >
                  View on Storefront ↗
                </Link>
                <Link
                  href={`/admin/products/${c.product.id}`}
                  className="button button-outline"
                  style={{ fontSize: 12, padding: "4px 10px", background: "#fff", color: "#073D26" }}
                >
                  Edit Product Details
                </Link>
              </div>
            </div>

            {/* Variants for this combo */}
            {c.variants.map((vg) => {
              const maxPackable = calculateMaxPackable(vg.components);
              const isAddingHere = addingVariantId === vg.variant.id;

              return (
                <div
                  key={vg.variant.id}
                  style={{
                    background: "#FBF9F5",
                    border: "1px solid #EAE3D5",
                    borderRadius: 10,
                    padding: 16,
                    marginBottom: 16,
                  }}
                >
                  <div
                    style={{
                      display: "flex",
                      alignItems: "center",
                      justifyContent: "space-between",
                      marginBottom: 12,
                      flexWrap: "wrap",
                      gap: 8,
                    }}
                  >
                    <div>
                      <strong style={{ fontSize: 15, color: "#073D26" }}>
                        Pack: {vg.variant.label}
                      </strong>
                      <span style={{ marginLeft: 10, fontSize: 13, color: "#665C4E" }}>
                        Selling Price: <strong>{formatINR(vg.variant.pricePaise)}</strong>
                      </span>
                    </div>

                    {/* Stock Status Badge */}
                    <div>
                      {maxPackable === null ? (
                        <span className="badge badge-neutral">No items linked</span>
                      ) : maxPackable === 0 ? (
                        <span className="badge badge-danger">
                          Out of Stock (0 complete boxes packable)
                        </span>
                      ) : maxPackable <= 5 ? (
                        <span className="badge badge-warning">
                          Low Stock: Can pack {maxPackable} hampers today
                        </span>
                      ) : (
                        <span className="badge badge-success">
                          ✓ Can pack {maxPackable} hampers right now
                        </span>
                      )}
                    </div>
                  </div>

                  {/* Components List */}
                  {vg.components.length === 0 ? (
                    <div
                      style={{
                        padding: "16px 20px",
                        background: "#fff",
                        borderRadius: 8,
                        border: "1px dashed #D6C8B5",
                        textAlign: "center",
                      }}
                    >
                      <p style={{ margin: "0 0 8px", fontSize: 13, color: "#666" }}>
                        No dry fruit items have been added to this hamper yet.
                      </p>
                      <button
                        type="button"
                        onClick={() => {
                          setAddingVariantId(vg.variant.id);
                          setError(null);
                        }}
                        className="button button-outline"
                        style={{ fontSize: 12, padding: "4px 12px" }}
                      >
                        + Add First Component
                      </button>
                    </div>
                  ) : (
                    <div className="admin-table-wrap" style={{ background: "#fff", borderRadius: 8 }}>
                      <table className="admin-table" style={{ margin: 0 }}>
                        <thead>
                          <tr>
                            <th>Included Dry Fruit</th>
                            <th style={{ width: 140 }}>Quantity in Hamper</th>
                            <th style={{ width: 160 }}>Live Warehouse Stock</th>
                            <th style={{ width: 180 }}>Fulfillment Limit</th>
                            <th style={{ width: 80, textAlign: "right" }}>Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          {vg.components.map((comp) => {
                            const canMakeFromThis = Math.floor(
                              comp.componentStock / (comp.quantity || 1)
                            );
                            const isBottleneck =
                              maxPackable !== null && canMakeFromThis === maxPackable;

                            return (
                              <tr key={comp.id}>
                                <td>
                                  <strong>{comp.componentProductName}</strong>
                                  <span style={{ display: "block", fontSize: 11, color: "#777" }}>
                                    Pack size: {comp.componentLabel} &bull; SKU: {comp.componentSku}
                                  </span>
                                </td>
                                <td>
                                  <span
                                    style={{
                                      display: "inline-block",
                                      padding: "2px 8px",
                                      background: "#F4EEDF",
                                      borderRadius: 4,
                                      fontWeight: 600,
                                      fontSize: 12,
                                    }}
                                  >
                                    {comp.quantity}&times; per hamper
                                  </span>
                                </td>
                                <td>
                                  <span
                                    style={{
                                      fontWeight: 600,
                                      color: comp.componentStock <= 5 ? "#C45B28" : "#073D26",
                                    }}
                                  >
                                    {comp.componentStock} units
                                  </span>
                                </td>
                                <td>
                                  <span
                                    style={{
                                      fontSize: 12,
                                      color: isBottleneck ? "#C45B28" : "#555",
                                      fontWeight: isBottleneck ? 700 : 400,
                                    }}
                                  >
                                    Supports {canMakeFromThis} hampers {isBottleneck ? "(Bottleneck)" : ""}
                                  </span>
                                </td>
                                <td style={{ textAlign: "right" }}>
                                  <button
                                    type="button"
                                    onClick={() => handleRemoveComponent(comp.id)}
                                    style={{
                                      background: "none",
                                      border: "none",
                                      color: "#A73A2A",
                                      cursor: "pointer",
                                      fontSize: 12,
                                      textDecoration: "underline",
                                    }}
                                  >
                                    Remove
                                  </button>
                                </td>
                              </tr>
                            );
                          })}
                        </tbody>
                      </table>
                    </div>
                  )}

                  {/* Add Component Drawer/Row */}
                  <div style={{ marginTop: 12 }}>
                    {!isAddingHere ? (
                      <button
                        type="button"
                        onClick={() => {
                          setAddingVariantId(vg.variant.id);
                          setError(null);
                        }}
                        className="button button-outline"
                        style={{
                          fontSize: 12,
                          padding: "5px 12px",
                          background: "#fff",
                          color: "#073D26",
                        }}
                      >
                        + Add Dry Fruit to this Hamper
                      </button>
                    ) : (
                      <div
                        style={{
                          background: "#fff",
                          padding: "14px 16px",
                          borderRadius: 8,
                          border: "1px solid #C5A059",
                          marginTop: 8,
                        }}
                      >
                        <h4 style={{ margin: "0 0 10px", fontSize: 13, color: "#073D26" }}>
                          Choose Dry Fruit to bundle in this Hamper
                        </h4>
                        <div style={{ display: "flex", gap: 10, flexWrap: "wrap", alignItems: "flex-end" }}>
                          <div style={{ flex: 1, minWidth: 260 }}>
                            <label style={{ display: "block", fontSize: 12, marginBottom: 4, fontWeight: 600 }}>
                              Select Dry Fruit &amp; Size
                            </label>
                            <select
                              value={selectedCompId}
                              onChange={(e) => setSelectedCompId(e.target.value)}
                              style={{
                                width: "100%",
                                padding: "8px 10px",
                                borderRadius: 6,
                                border: "1px solid #CCC",
                                fontSize: 13,
                              }}
                            >
                              <option value="">— Select item from catalog —</option>
                              {available.map((ac) => (
                                <option key={ac.variant.id} value={ac.variant.id}>
                                  {ac.productName} — {ac.variant.label} ({ac.variant.stock} in stock)
                                </option>
                              ))}
                            </select>
                          </div>

                          <div style={{ width: 100 }}>
                            <label style={{ display: "block", fontSize: 12, marginBottom: 4, fontWeight: 600 }}>
                              Qty / Box
                            </label>
                            <input
                              type="number"
                              min="1"
                              max="10"
                              value={qty}
                              onChange={(e) => setQty(e.target.value)}
                              style={{
                                width: "100%",
                                padding: "8px 10px",
                                borderRadius: 6,
                                border: "1px solid #CCC",
                                fontSize: 13,
                              }}
                            />
                          </div>

                          <button
                            type="button"
                            onClick={() => handleAddComponent(vg.variant.id)}
                            className="button button-primary"
                            style={{ fontSize: 13, padding: "8px 16px" }}
                          >
                            Add to Box
                          </button>
                          <button
                            type="button"
                            onClick={() => {
                              setAddingVariantId(null);
                              setSelectedCompId("");
                            }}
                            className="button button-outline"
                            style={{ fontSize: 13, padding: "8px 12px" }}
                          >
                            Cancel
                          </button>
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        ))
      )}
    </div>
  );
}
