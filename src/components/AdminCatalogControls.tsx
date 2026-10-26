"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";

export function AdminCatalogControls() {
  const router = useRouter();
  const [loading, setLoading] = useState(false);

  async function handleWipe() {
    const confirmed = window.confirm(
      "Are you sure you want to completely wipe the catalog? This will delete all products, variants, and categories so you can start from scratch."
    );
    if (!confirmed) return;

    setLoading(true);
    try {
      const res = await fetch("/api/admin/catalog/wipe", { method: "POST" });
      const data = await res.json();
      if (!res.ok || !data.ok) {
        alert("Failed to wipe catalog: " + (data.error || "Unknown error"));
      } else {
        alert("Entire catalog has been wiped clean (0 products, 0 categories).");
        router.refresh();
      }
    } catch (err: any) {
      alert("Error wiping catalog: " + (err.message || String(err)));
    } finally {
      setLoading(false);
    }
  }

  async function handleSeedRealistic() {
    const confirmed = window.confirm(
      "Load realistic dry fruits catalog? This will populate 13 authentic products (Almonds, Cashews, Pistachios, Walnuts, Dates, Figs, Raisins, Seeds, Gift Box, Organic Walnuts) with realistic Indian retail prices, pack sizes, and images."
    );
    if (!confirmed) return;

    setLoading(true);
    try {
      const res = await fetch("/api/admin/catalog/seed-realistic", { method: "POST" });
      const data = await res.json();
      if (!res.ok || !data.ok) {
        alert("Failed to load realistic catalog: " + (data.error || "Unknown error"));
      } else {
        alert("Realistic dry fruits catalog successfully loaded!");
        router.refresh();
      }
    } catch (err: any) {
      alert("Error loading realistic catalog: " + (err.message || String(err)));
    } finally {
      setLoading(false);
    }
  }

  return (
    <div style={{ display: "flex", alignItems: "center", gap: 10, flexWrap: "wrap" }}>
      <button
        type="button"
        disabled={loading}
        onClick={handleSeedRealistic}
        style={{
          display: "inline-flex",
          alignItems: "center",
          gap: 6,
          background: "#F0FDF4",
          color: "#166534",
          border: "1px solid #BBF7D0",
          borderRadius: 8,
          padding: "8px 14px",
          fontSize: 13,
          fontWeight: 600,
          cursor: loading ? "not-allowed" : "pointer",
          transition: "all 0.15s ease",
        }}
        title="1-click populate realistic dry fruits with accurate INR market prices and images"
      >
        <span>⚡</span>
        <span>Load Realistic Catalog</span>
      </button>

      <button
        type="button"
        disabled={loading}
        onClick={handleWipe}
        style={{
          display: "inline-flex",
          alignItems: "center",
          gap: 6,
          background: "#FEF2F2",
          color: "#991B1B",
          border: "1px solid #FECACA",
          borderRadius: 8,
          padding: "8px 14px",
          fontSize: 13,
          fontWeight: 600,
          cursor: loading ? "not-allowed" : "pointer",
          transition: "all 0.15s ease",
        }}
        title="Wipe all products and categories clean to add from scratch"
      >
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <path d="M3 6h18m-2 0v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6m3 0V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
        </svg>
        <span>Wipe Catalog</span>
      </button>
    </div>
  );
}
