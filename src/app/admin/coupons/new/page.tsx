"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";

export default function AdminNewCouponPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [formData, setFormData] = useState({
    code: "",
    name: "",
    description: "",
    type: "percent",
    value: "10",
    minSubtotalINR: "500",
    maxDiscountINR: "",
    usageLimit: "100",
    startsAt: "",
    expiresAt: "",
    active: true,
  });

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError(null);

    const minSubtotalPaise = Math.round(Number(formData.minSubtotalINR || 0) * 100);
    const maxDiscountPaise = formData.maxDiscountINR ? Math.round(Number(formData.maxDiscountINR) * 100) : null;
    const usageLimit = formData.usageLimit ? Number(formData.usageLimit) : null;
    let numericValue = Number(formData.value || 0);
    if (formData.type === "fixed") {
      numericValue = numericValue * 100; // in paise
    } else if (formData.type === "free_shipping") {
      numericValue = 0;
    }

    try {
      const res = await fetch("/api/admin/coupons", {
        method: "POST",
        headers: { "content-type": "application/json" },
        body: JSON.stringify({
          code: formData.code.trim().toUpperCase(),
          name: formData.name.trim() || undefined,
          description: formData.description.trim() || undefined,
          type: formData.type,
          value: numericValue,
          minSubtotalPaise,
          maxDiscountPaise,
          usageLimit,
          startsAt: formData.startsAt ? new Date(formData.startsAt).toISOString() : undefined,
          expiresAt: formData.expiresAt ? new Date(formData.expiresAt).toISOString() : undefined,
          active: formData.active,
        }),
      });

      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Failed to create coupon");

      router.push("/admin/coupons");
      router.refresh();
    } catch (err: any) {
      setError(err.message || "Operation failed.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="coupon-v3-page-wrap" style={{ maxWidth: 860 }}>
      {/* Header Banner */}
      <div className="coupon-v3-header-wrap" style={{ minHeight: 180, marginBottom: 28 }}>
        <div className="coupon-v3-header-left">
          <div className="admin-v3-breadcrumbs">
            <Link href="/admin">Dashboard</Link>
            <span aria-hidden="true">/</span>
            <Link href="/admin/coupons">Coupons</Link>
            <span aria-hidden="true">/</span>
            <span>Create</span>
          </div>
          <h1 className="coupon-v3-title">
            Create New <em>Coupon</em>
          </h1>
          <p className="coupon-v3-subtitle">
            Configure code, discount percentage or fixed cash reduction, validity, and limits.
          </p>
        </div>
      </div>

      <div style={{ background: "#FFFFFF", borderRadius: 16, border: "1px solid #ECE7DE", padding: 32, boxShadow: "0 4px 20px rgba(0,0,0,0.03)" }}>
        {error && (
          <div className="coupon-v3-modal-error">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="coupon-v3-modal-form">
          <div className="coupon-v3-modal-grid">
            {/* Code */}
            <div className="coupon-v3-field">
              <label className="coupon-v3-label">Coupon Code *</label>
              <input
                type="text"
                required
                placeholder="e.g. FESTIVE20"
                value={formData.code}
                onChange={(e) => setFormData({ ...formData, code: e.target.value.toUpperCase() })}
                className="coupon-v3-input"
                style={{ textTransform: "uppercase", fontWeight: 700, letterSpacing: "0.05em" }}
              />
            </div>

            {/* Name */}
            <div className="coupon-v3-field">
              <label className="coupon-v3-label">Coupon Title / Campaign Name</label>
              <input
                type="text"
                placeholder="e.g. Diwali Dhamaka 20% Off"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                className="coupon-v3-input"
              />
            </div>
          </div>

          {/* Description */}
          <div className="coupon-v3-field">
            <label className="coupon-v3-label">Description / Customer Terms</label>
            <input
              type="text"
              placeholder="e.g. Applicable on orders above ₹500 across all dried fruits."
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              className="coupon-v3-input"
            />
          </div>

          <div className="coupon-v3-modal-grid">
            {/* Type */}
            <div className="coupon-v3-field">
              <label className="coupon-v3-label">Discount Type *</label>
              <select
                value={formData.type}
                onChange={(e) => setFormData({ ...formData, type: e.target.value })}
                className="coupon-v3-input"
              >
                <option value="percent">Percentage (%)</option>
                <option value="fixed">Fixed Amount (₹)</option>
                <option value="free_shipping">Free Shipping</option>
              </select>
            </div>

            {/* Value */}
            <div className="coupon-v3-field">
              <label className="coupon-v3-label">
                {formData.type === "percent"
                  ? "Discount Percent (%) *"
                  : formData.type === "fixed"
                  ? "Discount Amount (₹) *"
                  : "Value"}
              </label>
              <input
                type="number"
                min="0"
                disabled={formData.type === "free_shipping"}
                required={formData.type !== "free_shipping"}
                value={formData.type === "free_shipping" ? "0" : formData.value}
                onChange={(e) => setFormData({ ...formData, value: e.target.value })}
                className="coupon-v3-input"
              />
            </div>
          </div>

          <div className="coupon-v3-modal-grid">
            {/* Min Subtotal */}
            <div className="coupon-v3-field">
              <label className="coupon-v3-label">Minimum Order Value (₹) *</label>
              <input
                type="number"
                min="0"
                required
                value={formData.minSubtotalINR}
                onChange={(e) => setFormData({ ...formData, minSubtotalINR: e.target.value })}
                className="coupon-v3-input"
              />
            </div>

            {/* Usage Limit */}
            <div className="coupon-v3-field">
              <label className="coupon-v3-label">Total Usage Limit</label>
              <input
                type="number"
                min="1"
                placeholder="Unlimited if left empty"
                value={formData.usageLimit}
                onChange={(e) => setFormData({ ...formData, usageLimit: e.target.value })}
                className="coupon-v3-input"
              />
            </div>
          </div>

          <div className="coupon-v3-modal-grid">
            {/* Starts At */}
            <div className="coupon-v3-field">
              <label className="coupon-v3-label">Valid From</label>
              <input
                type="date"
                value={formData.startsAt}
                onChange={(e) => setFormData({ ...formData, startsAt: e.target.value })}
                className="coupon-v3-input"
              />
            </div>

            {/* Expires At */}
            <div className="coupon-v3-field">
              <label className="coupon-v3-label">Valid Until / Expiration Date</label>
              <input
                type="date"
                value={formData.expiresAt}
                onChange={(e) => setFormData({ ...formData, expiresAt: e.target.value })}
                className="coupon-v3-input"
              />
            </div>
          </div>

          {/* Active Switch */}
          <label style={{ display: "flex", alignItems: "center", gap: 10, cursor: "pointer", marginTop: 8 }}>
            <input
              type="checkbox"
              checked={formData.active}
              onChange={(e) => setFormData({ ...formData, active: e.target.checked })}
              className="admin-v3-checkbox"
            />
            <span style={{ fontSize: 14, fontWeight: 500, color: "#1E2520" }}>
              Active and eligible for customer redemptions immediately
            </span>
          </label>

          <div style={{ display: "flex", alignItems: "center", justifyContent: "flex-end", gap: 12, marginTop: 16, paddingTop: 20, borderTop: "1px solid #ECE7DE" }}>
            <Link
              href="/admin/coupons"
              className="coupon-v3-modal-cancel"
              style={{ textDecoration: "none", display: "inline-flex", alignItems: "center" }}
            >
              Cancel
            </Link>
            <button
              type="submit"
              disabled={loading}
              className="coupon-v3-modal-submit"
              style={{ padding: "10px 24px", fontSize: 14 }}
            >
              {loading ? "Creating Coupon..." : "Publish Coupon"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
