"use client";

import Link from "next/link";
import { useEffect, useMemo, useState } from "react";
import { formatINR } from "@/lib/money";

type Coupon = {
  id: string;
  code: string;
  name?: string | null;
  description?: string | null;
  type: string;
  value: number;
  minSubtotalPaise: number;
  maxDiscountPaise: number | null;
  active: boolean;
  startsAt: string | null;
  expiresAt: string | null;
  usageLimit: number | null;
  timesUsed: number;
  totalOrders: number;
  totalDiscountPaise: number;
};

export default function AdminCouponsPage() {
  const [coupons, setCoupons] = useState<Coupon[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState("all");
  const [dateFilter, setDateFilter] = useState("all");
  const [selectedIds, setSelectedIds] = useState<string[]>([]);

  // Modal & Inline Form State
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [showInlineForm, setShowInlineForm] = useState(false);
  const [editingCoupon, setEditingCoupon] = useState<Coupon | null>(null);
  const [modalLoading, setModalLoading] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);

  // Form Fields
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

  // Pagination
  const [currentPage, setCurrentPage] = useState(1);
  const pageSize = 6;

  useEffect(() => {
    loadCoupons();
  }, []);

  function loadCoupons() {
    setLoading(true);
    fetch("/api/admin/coupons")
      .then((r) => r.json())
      .then((d) => {
        if (d.coupons) setCoupons(d.coupons);
        setLoading(false);
      })
      .catch(() => setLoading(false));
  }

  // Helper to determine real status: Scheduled, Expired, or Active
  function getCouponStatus(c: Coupon): "active" | "scheduled" | "expired" {
    const now = new Date();
    if (c.expiresAt && new Date(c.expiresAt) < now) {
      return "expired";
    }
    if (c.usageLimit && c.timesUsed >= c.usageLimit) {
      return "expired";
    }
    if (c.startsAt && new Date(c.startsAt) > now) {
      return "scheduled";
    }
    if (!c.active) {
      return "expired";
    }
    return "active";
  }

  // Filtered list
  const filtered = useMemo(() => {
    return coupons.filter((c) => {
      // Search
      if (search.trim()) {
        const q = search.toLowerCase();
        const codeMatch = c.code.toLowerCase().includes(q);
        const nameMatch = (c.name || "").toLowerCase().includes(q);
        const descMatch = (c.description || "").toLowerCase().includes(q);
        if (!codeMatch && !nameMatch && !descMatch) return false;
      }

      // Status
      const status = getCouponStatus(c);
      if (statusFilter !== "all" && status !== statusFilter) {
        return false;
      }

      // Date range filter
      if (dateFilter !== "all") {
        if (dateFilter === "active_now" && status !== "active") return false;
      }

      return true;
    });
  }, [coupons, search, statusFilter, dateFilter]);

  // Paginated slice
  const totalItems = filtered.length;
  const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
  const paginatedCoupons = useMemo(() => {
    const start = (currentPage - 1) * pageSize;
    return filtered.slice(start, start + pageSize);
  }, [filtered, currentPage, pageSize]);

  // KPI Metrics
  const totalCoupons = coupons.length;
  const activeCoupons = coupons.filter((c) => getCouponStatus(c) === "active").length;
  const scheduledCoupons = coupons.filter((c) => getCouponStatus(c) === "scheduled").length;
  const expiredCoupons = coupons.filter((c) => getCouponStatus(c) === "expired").length;

  // Selection
  const allSelected = paginatedCoupons.length > 0 && paginatedCoupons.every((c) => selectedIds.includes(c.id));
  const toggleSelectAll = () => {
    if (allSelected) {
      setSelectedIds([]);
    } else {
      setSelectedIds(paginatedCoupons.map((c) => c.id));
    }
  };

  const toggleSelect = (id: string) => {
    setSelectedIds((prev) =>
      prev.includes(id) ? prev.filter((i) => i !== id) : [...prev, id]
    );
  };

  // Open Create Modal
  const openCreateModal = () => {
    setEditingCoupon(null);
    setFormData({
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
    setModalError(null);
    setIsModalOpen(true);
  };

  // Open Edit Modal
  const openEditModal = (c: Coupon) => {
    setEditingCoupon(c);
    const valStr = c.type === "fixed" ? String(Math.round(c.value / 100)) : String(c.value);
    setFormData({
      code: c.code,
      name: c.name || "",
      description: c.description || "",
      type: c.type,
      value: valStr,
      minSubtotalINR: String(Math.round(c.minSubtotalPaise / 100)),
      maxDiscountINR: c.maxDiscountPaise ? String(Math.round(c.maxDiscountPaise / 100)) : "",
      usageLimit: c.usageLimit ? String(c.usageLimit) : "",
      startsAt: c.startsAt ? c.startsAt.slice(0, 10) : "",
      expiresAt: c.expiresAt ? c.expiresAt.slice(0, 10) : "",
      active: c.active,
    });
    setModalError(null);
    setIsModalOpen(true);
  };

  // Save Coupon (Create or Update)
  async function handleSaveCoupon(e: React.FormEvent) {
    e.preventDefault();
    setModalLoading(true);
    setModalError(null);

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
      if (editingCoupon) {
        // PUT update
        const res = await fetch("/api/admin/coupons", {
          method: "PUT",
          headers: { "content-type": "application/json" },
          body: JSON.stringify({
            id: editingCoupon.id,
            name: formData.name,
            description: formData.description,
            value: numericValue,
            minSubtotalPaise,
            usageLimit,
            startsAt: formData.startsAt ? new Date(formData.startsAt).toISOString() : null,
            expiresAt: formData.expiresAt ? new Date(formData.expiresAt).toISOString() : null,
            active: formData.active,
          }),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || "Failed to update coupon");
      } else {
        // POST create
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
      }

      setIsModalOpen(false);
      loadCoupons();
    } catch (err: any) {
      setModalError(err.message || "Operation failed.");
    } finally {
      setModalLoading(false);
    }
  }

  // Delete Coupon
  async function handleDelete(c: Coupon) {
    if (!window.confirm(`Delete coupon "${c.code}"?\nThis cannot be undone.`)) return;
    try {
      const res = await fetch(`/api/admin/coupons?id=${c.id}`, { method: "DELETE" });
      if (!res.ok) throw new Error("Delete failed");
      loadCoupons();
    } catch {
      alert("Failed to delete coupon.");
    }
  }

  // Format validity date range (e.g. "01 Apr 2025 – 30 Apr 2025")
  function formatValidity(startsAt: string | null, expiresAt: string | null) {
    const formatDate = (iso: string) => {
      const d = new Date(iso);
      return d.toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
    };

    if (startsAt && expiresAt) {
      return `${formatDate(startsAt)} – ${formatDate(expiresAt)}`;
    }
    if (expiresAt) {
      return `Until ${formatDate(expiresAt)}`;
    }
    if (startsAt) {
      return `From ${formatDate(startsAt)}`;
    }
    return "No expiration";
  }

  return (
    <div className="coupon-v3-page-wrap">
      {/* Header Banner */}
      <div className="coupon-v3-header-wrap">
        <div className="coupon-v3-header-left">
          <div className="admin-v3-breadcrumbs">
            <Link href="/admin">Dashboard</Link>
            <span aria-hidden="true">/</span>
            <span>Coupons</span>
          </div>
          <h1 className="coupon-v3-title">
            Coupons &amp; <em>Offers</em>
          </h1>
          <p className="coupon-v3-subtitle">
            Create and manage discount coupons to boost sales and reward your customers.
          </p>
        </div>
        <div className="admin-v3-header-right">
          <button
            type="button"
            onClick={openCreateModal}
            className="admin-v3-btn-create-order"
          >
            <span aria-hidden="true" style={{ fontSize: 16, fontWeight: 700 }}>+</span> Create Coupon
          </button>
          <button
            type="button"
            onClick={() => {
              const headers = "Code,Name,Type,Value,Min Order (Paise),Active,Times Used\n";
              const rows = coupons
                .map((c) => `"${c.code}","${c.name || ""}","${c.type}",${c.value},${c.minSubtotalPaise},${c.active},${c.timesUsed}`)
                .join("\n");
              const blob = new Blob([headers + rows], { type: "text/csv;charset=utf-8;" });
              const url = URL.createObjectURL(blob);
              const a = document.createElement("a");
              a.href = url;
              a.download = `coupons-export-${new Date().toISOString().slice(0, 10)}.csv`;
              a.click();
            }}
            className="admin-v3-btn-export"
          >
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
              <polyline points="7 10 12 15 17 10" />
              <line x1="12" y1="15" x2="12" y2="3" />
            </svg>
            Export Coupons
          </button>
        </div>
      </div>


      {/* 4 KPI Metric Cards */}
      <div className="admin-v3-kpi-grid">
        {/* Card 1: Total Coupons */}
        <div className="admin-v3-kpi-card">
          <div className="admin-v3-kpi-left">
            <div className="admin-v3-kpi-icon-box" style={{ background: "#EAF5EC", color: "#2E7D32" }}>
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" />
                <line x1="7" y1="7" x2="7.01" y2="7" />
              </svg>
            </div>
            <div className="admin-v3-kpi-info">
              <span className="admin-v3-kpi-num">{totalCoupons}</span>
              <span className="admin-v3-kpi-label">TOTAL COUPONS</span>
            </div>
          </div>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src="/assets/singlebadam.png" alt="" className="admin-kpi-thumb" />
        </div>

        {/* Card 2: Active Coupons */}
        <div className="admin-v3-kpi-card">
          <div className="admin-v3-kpi-left">
            <div className="admin-v3-kpi-icon-box" style={{ background: "#F7F1E5", color: "#8E6D38" }}>
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                <rect x="2" y="5" width="20" height="14" rx="2" />
                <line x1="2" y1="10" x2="22" y2="10" />
              </svg>
            </div>
            <div className="admin-v3-kpi-info">
              <span className="admin-v3-kpi-num">{activeCoupons}</span>
              <span className="admin-v3-kpi-label">ACTIVE COUPONS</span>
            </div>
          </div>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src="/assets/singlepistaproduct.png" alt="" className="admin-kpi-thumb" />
        </div>

        {/* Card 3: Scheduled Coupons */}
        <div className="admin-v3-kpi-card">
          <div className="admin-v3-kpi-left">
            <div className="admin-v3-kpi-icon-box" style={{ background: "#FEF6E9", color: "#B4691B" }}>
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                <circle cx="12" cy="12" r="10" />
                <polyline points="12 6 12 12 16 14" />
              </svg>
            </div>
            <div className="admin-v3-kpi-info">
              <span className="admin-v3-kpi-num">{scheduledCoupons}</span>
              <span className="admin-v3-kpi-label">SCHEDULED COUPONS</span>
            </div>
          </div>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src="/assets/acrod%20image.png" alt="" className="admin-kpi-thumb" />
        </div>

        {/* Card 4: Expired Coupons */}
        <div className="admin-v3-kpi-card">
          <div className="admin-v3-kpi-left">
            <div className="admin-v3-kpi-icon-box" style={{ background: "#FDF0ED", color: "#C24B38" }}>
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                <circle cx="12" cy="12" r="10" />
                <line x1="4.93" y1="4.93" x2="19.07" y2="19.07" />
              </svg>
            </div>
            <div className="admin-v3-kpi-info">
              <span className="admin-v3-kpi-num">{expiredCoupons}</span>
              <span className="admin-v3-kpi-label">EXPIRED COUPONS</span>
            </div>
          </div>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src="/assets/drydate%20image.png" alt="" className="admin-kpi-thumb" />
        </div>
      </div>

      {/* Search & Filter Toolbar */}
      <div className="admin-v3-toolbar">
        <div className="admin-v3-search-wrap">
          <span className="admin-v3-search-icon">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <circle cx="11" cy="11" r="8" />
              <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
          </span>
          <input
            type="text"
            value={search}
            onChange={(e) => {
              setSearch(e.target.value);
              setCurrentPage(1);
            }}
            placeholder="Search by coupon code, name, or description..."
            className="admin-v3-search-input"
          />
        </div>

        {/* Status Dropdown */}
        <select
          value={statusFilter}
          onChange={(e) => {
            setStatusFilter(e.target.value);
            setCurrentPage(1);
          }}
          className="admin-v3-cat-select"
        >
          <option value="all">All Statuses</option>
          <option value="active">Active</option>
          <option value="scheduled">Scheduled</option>
          <option value="expired">Expired</option>
        </select>

        {/* Date Dropdown */}
        <div className="coupon-v3-date-select-wrap">
          <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className="coupon-v3-date-icon">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
            <line x1="16" y1="2" x2="16" y2="6" />
            <line x1="8" y1="2" x2="8" y2="6" />
            <line x1="3" y1="10" x2="21" y2="10" />
          </svg>
          <select
            value={dateFilter}
            onChange={(e) => {
              setDateFilter(e.target.value);
              setCurrentPage(1);
            }}
            className="coupon-v3-date-select"
          >
            <option value="all">All Dates</option>
            <option value="active_now">Active Now</option>
          </select>
        </div>

        {/* Create Coupon Button */}
        <button
          type="button"
          onClick={() => {
            openCreateModal();
            setShowInlineForm(true);
          }}
          className="admin-v3-btn-create"
        >
          <span aria-hidden="true">+</span> Create Coupon
        </button>
      </div>

      {/* Inline Create Coupon Form (Always easily accessible) */}
      {showInlineForm && (
        <div className="coupon-v3-inline-create-box">
          <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", marginBottom: 20, borderBottom: "1px solid #ECE7DE", paddingBottom: 14 }}>
            <div>
              <h2 style={{ fontFamily: "Playfair Display, serif", fontSize: 20, color: "#143D24", margin: 0, fontWeight: 600 }}>
                {editingCoupon ? `Edit Coupon #${editingCoupon.code}` : "Create New Discount Coupon"}
              </h2>
              <p style={{ fontSize: 13, color: "#6B6255", margin: "4px 0 0" }}>
                Fill in the coupon details below to make it available for customer redemptions immediately.
              </p>
            </div>
            <button
              type="button"
              onClick={() => {
                setShowInlineForm(false);
                setIsModalOpen(false);
              }}
              style={{ background: "none", border: "none", fontSize: 20, color: "#8C8476", cursor: "pointer", padding: "4px 8px" }}
            >
              ✕
            </button>
          </div>

          {modalError && (
            <div className="coupon-v3-modal-error">
              {modalError}
            </div>
          )}

          <form onSubmit={handleSaveCoupon} className="coupon-v3-modal-form">
            <div className="coupon-v3-modal-grid">
              {/* Code */}
              <div className="coupon-v3-field">
                <label className="coupon-v3-label">Coupon Code *</label>
                <input
                  type="text"
                  required
                  placeholder="e.g. SVGDIWALI"
                  disabled={!!editingCoupon}
                  value={formData.code}
                  onChange={(e) => setFormData({ ...formData, code: e.target.value.toUpperCase() })}
                  className="coupon-v3-input"
                  style={{ textTransform: "uppercase", fontWeight: 700 }}
                />
              </div>

              {/* Name */}
              <div className="coupon-v3-field">
                <label className="coupon-v3-label">Coupon Name / Campaign</label>
                <input
                  type="text"
                  placeholder="e.g. Diwali Festive Offer"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  className="coupon-v3-input"
                />
              </div>
            </div>

            {/* Description */}
            <div className="coupon-v3-field">
              <label className="coupon-v3-label">Description (Shown to shoppers in cart)</label>
              <input
                type="text"
                placeholder="e.g. Flat 15% discount on all orders above ₹500"
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
                  placeholder="Unlimited if empty"
                  value={formData.usageLimit}
                  onChange={(e) => setFormData({ ...formData, usageLimit: e.target.value })}
                  className="coupon-v3-input"
                />
              </div>
            </div>

            <div className="coupon-v3-modal-grid">
              {/* Starts At */}
              <div className="coupon-v3-field">
                <label className="coupon-v3-label">Start Date</label>
                <input
                  type="date"
                  value={formData.startsAt}
                  onChange={(e) => setFormData({ ...formData, startsAt: e.target.value })}
                  className="coupon-v3-input"
                />
              </div>

              {/* Expires At */}
              <div className="coupon-v3-field">
                <label className="coupon-v3-label">Expiration Date</label>
                <input
                  type="date"
                  value={formData.expiresAt}
                  onChange={(e) => setFormData({ ...formData, expiresAt: e.target.value })}
                  className="coupon-v3-input"
                />
              </div>
            </div>

            <label style={{ display: "flex", alignItems: "center", gap: 10, cursor: "pointer", marginTop: 4 }}>
              <input
                type="checkbox"
                checked={formData.active}
                onChange={(e) => setFormData({ ...formData, active: e.target.checked })}
                className="admin-v3-checkbox"
              />
              <span style={{ fontSize: 13.5, fontWeight: 500, color: "#1E2520" }}>
                Active and available for checkout
              </span>
            </label>

            <div style={{ display: "flex", justifyContent: "flex-end", gap: 12, borderTop: "1px solid #ECE7DE", paddingTop: 16, marginTop: 10 }}>
              <button
                type="button"
                onClick={() => {
                  setShowInlineForm(false);
                  setIsModalOpen(false);
                }}
                className="coupon-v3-modal-cancel"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={modalLoading}
                className="coupon-v3-modal-submit"
              >
                {modalLoading ? "Saving..." : editingCoupon ? "Save Changes" : "Create Coupon"}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* Coupons Table */}
      <div className="admin-v3-table-card">

        <div className="admin-v3-table-scroll">
          <table className="admin-v3-table">
            <thead>
              <tr>
                <th style={{ width: 42, paddingLeft: 18 }}>
                  <input
                    type="checkbox"
                    checked={allSelected}
                    onChange={toggleSelectAll}
                    className="admin-v3-checkbox"
                    aria-label="Select all coupons"
                  />
                </th>
                <th>COUPON CODE</th>
                <th>COUPON NAME</th>
                <th>DISCOUNT TYPE</th>
                <th>DISCOUNT VALUE</th>
                <th>MIN. ORDER</th>
                <th>USAGE LIMIT</th>
                <th>VALIDITY PERIOD</th>
                <th>STATUS</th>
                <th style={{ textAlign: "right", paddingRight: 24 }}>ACTIONS</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td colSpan={10} style={{ textAlign: "center", padding: "48px 20px", color: "#7D7567" }}>
                    Loading coupons...
                  </td>
                </tr>
              ) : paginatedCoupons.length === 0 ? (
                <tr>
                  <td colSpan={10} style={{ textAlign: "center", padding: "48px 20px", color: "#7D7567" }}>
                    No coupons found matching your criteria.
                  </td>
                </tr>
              ) : (
                paginatedCoupons.map((c) => {
                  const status = getCouponStatus(c);
                  const isChecked = selectedIds.includes(c.id);

                  // Formatting discount value
                  let formattedValue = `${c.value}%`;
                  if (c.type === "fixed") {
                    formattedValue = formatINR(c.value);
                  } else if (c.type === "free_shipping") {
                    formattedValue = "Free";
                  }

                  // Usage display
                  const usageDisplay = c.usageLimit
                    ? `${c.timesUsed} / ${c.usageLimit}`
                    : `${c.timesUsed} / ∞`;
                  const isLimitReached = Boolean(c.usageLimit && c.timesUsed >= c.usageLimit);

                  return (
                    <tr key={c.id} className={isChecked ? "admin-v3-row-selected" : ""}>
                      <td style={{ paddingLeft: 18 }}>
                        <input
                          type="checkbox"
                          checked={isChecked}
                          onChange={() => toggleSelect(c.id)}
                          className="admin-v3-checkbox"
                          aria-label={`Select coupon ${c.code}`}
                        />
                      </td>

                      {/* Code Badge */}
                      <td>
                        <span className="coupon-v3-code-badge">
                          {c.code}
                        </span>
                      </td>

                      {/* Name & Description */}
                      <td>
                        <div style={{ display: "flex", flexDirection: "column", gap: 2 }}>
                          <span style={{ fontWeight: 600, color: "#1E2520", fontSize: 13.5 }}>
                            {c.name || c.code}
                          </span>
                          {c.description && (
                            <span style={{ fontSize: 12, color: "#7D7567" }}>
                              {c.description}
                            </span>
                          )}
                        </div>
                      </td>

                      {/* Discount Type Pill */}
                      <td>
                        {c.type === "percent" ? (
                          <span className="coupon-v3-type-percentage">
                            Percentage
                          </span>
                        ) : c.type === "fixed" ? (
                          <span className="coupon-v3-type-fixed">
                            Fixed Amount
                          </span>
                        ) : (
                          <span className="coupon-v3-type-shipping">
                            Free Shipping
                          </span>
                        )}
                      </td>

                      {/* Discount Value */}
                      <td style={{ fontWeight: 600, color: "#1E2520", fontSize: 13.5 }}>
                        {formattedValue}
                      </td>

                      {/* Min Order */}
                      <td style={{ color: "#4A4033", fontSize: 13.5, fontWeight: 500 }}>
                        {formatINR(c.minSubtotalPaise)}
                      </td>

                      {/* Usage Limit */}
                      <td style={{ fontSize: 13, color: isLimitReached ? "#C24B38" : "#4A4033", fontWeight: isLimitReached ? 700 : 500 }}>
                        {usageDisplay}
                      </td>

                      {/* Validity Period */}
                      <td style={{ fontSize: 12.5, color: "#6B6255" }}>
                        {formatValidity(c.startsAt, c.expiresAt)}
                      </td>

                      {/* Status */}
                      <td>
                        {status === "active" ? (
                          <span className="coupon-v3-status-active">
                            ● Active
                          </span>
                        ) : status === "scheduled" ? (
                          <span className="coupon-v3-status-scheduled">
                            ● Scheduled
                          </span>
                        ) : (
                          <span className="coupon-v3-status-expired">
                            ● Expired
                          </span>
                        )}
                      </td>

                      {/* Actions */}
                      <td style={{ textAlign: "right", paddingRight: 24 }}>
                        <div style={{ display: "inline-flex", alignItems: "center", gap: 8 }}>
                          <button
                            type="button"
                            onClick={() => openEditModal(c)}
                            className="admin-v3-btn-edit"
                          >
                            Edit
                          </button>
                          <button
                            type="button"
                            onClick={() => handleDelete(c)}
                            className="admin-v3-btn-delete"
                          >
                            Delete
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>

        {/* Footer with Pagination */}
        <div className="admin-v3-table-footer">
          <span className="admin-v3-footer-text">
            Showing {filtered.length === 0 ? 0 : (currentPage - 1) * pageSize + 1}–
            {Math.min(currentPage * pageSize, filtered.length)} of {filtered.length} coupons
          </span>

          <div className="admin-v3-pagination">
            <button
              type="button"
              disabled={currentPage <= 1}
              onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
              className="admin-v3-page-btn"
              aria-label="Previous Page"
            >
              ‹
            </button>
            {Array.from({ length: totalPages }, (_, i) => i + 1).map((p) => (
              <button
                key={p}
                type="button"
                onClick={() => setCurrentPage(p)}
                className={`admin-v3-page-btn ${currentPage === p ? "admin-v3-page-active" : ""}`}
              >
                {p}
              </button>
            ))}
            <button
              type="button"
              disabled={currentPage >= totalPages}
              onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
              className="admin-v3-page-btn"
              aria-label="Next Page"
            >
              ›
            </button>
          </div>
        </div>
      </div>

      {/* Create / Edit Coupon Modal */}
      {isModalOpen && (
        <div className="coupon-v3-modal-backdrop" onClick={() => setIsModalOpen(false)}>
          <div className="coupon-v3-modal-card" onClick={(e) => e.stopPropagation()}>
            <div className="coupon-v3-modal-header">
              <h2 className="coupon-v3-modal-title">
                {editingCoupon ? `Edit Coupon: ${editingCoupon.code}` : "Create New Coupon"}
              </h2>
              <button
                type="button"
                onClick={() => setIsModalOpen(false)}
                className="coupon-v3-modal-close"
                aria-label="Close modal"
              >
                ✕
              </button>
            </div>

            {modalError && (
              <div className="coupon-v3-modal-error">
                {modalError}
              </div>
            )}

            <form onSubmit={handleSaveCoupon} className="coupon-v3-modal-form">
              <div className="coupon-v3-modal-grid">
                {/* Code */}
                <div className="coupon-v3-field">
                  <label className="coupon-v3-label">Coupon Code *</label>
                  <input
                    type="text"
                    required
                    disabled={Boolean(editingCoupon)}
                    value={formData.code}
                    onChange={(e) => setFormData({ ...formData, code: e.target.value.toUpperCase() })}
                    placeholder="e.g. WELCOME10"
                    className="coupon-v3-input"
                    style={{ textTransform: "uppercase", letterSpacing: "0.06em", fontWeight: 600 }}
                  />
                </div>

                {/* Name */}
                <div className="coupon-v3-field">
                  <label className="coupon-v3-label">Coupon Name *</label>
                  <input
                    type="text"
                    required
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    placeholder="e.g. Welcome Offer"
                    className="coupon-v3-input"
                  />
                </div>
              </div>

              {/* Description */}
              <div className="coupon-v3-field">
                <label className="coupon-v3-label">Short Description</label>
                <input
                  type="text"
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  placeholder="e.g. Flat 10% off on first order"
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
                      ? "Percentage Discount (%)"
                      : formData.type === "fixed"
                      ? "Discount Amount (₹)"
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
                  <label className="coupon-v3-label">Minimum Order (₹) *</label>
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
                  <label className="coupon-v3-label">Usage Limit (Leave blank for unlimited)</label>
                  <input
                    type="number"
                    min="1"
                    value={formData.usageLimit}
                    onChange={(e) => setFormData({ ...formData, usageLimit: e.target.value })}
                    placeholder="e.g. 200"
                    className="coupon-v3-input"
                  />
                </div>
              </div>

              <div className="coupon-v3-modal-grid">
                {/* Starts At */}
                <div className="coupon-v3-field">
                  <label className="coupon-v3-label">Start Date</label>
                  <input
                    type="date"
                    value={formData.startsAt}
                    onChange={(e) => setFormData({ ...formData, startsAt: e.target.value })}
                    className="coupon-v3-input"
                  />
                </div>

                {/* Expires At */}
                <div className="coupon-v3-field">
                  <label className="coupon-v3-label">Expiration Date</label>
                  <input
                    type="date"
                    value={formData.expiresAt}
                    onChange={(e) => setFormData({ ...formData, expiresAt: e.target.value })}
                    className="coupon-v3-input"
                  />
                </div>
              </div>

              {/* Active Toggle */}
              <label style={{ display: "flex", alignItems: "center", gap: 10, cursor: "pointer", marginTop: 4 }}>
                <input
                  type="checkbox"
                  checked={formData.active}
                  onChange={(e) => setFormData({ ...formData, active: e.target.checked })}
                  className="admin-v3-checkbox"
                />
                <span style={{ fontSize: 13.5, fontWeight: 500, color: "#1E2520" }}>
                  Active and available for checkout
                </span>
              </label>

              {/* Actions */}
              <div className="coupon-v3-modal-footer">
                <button
                  type="button"
                  onClick={() => setIsModalOpen(false)}
                  className="coupon-v3-modal-cancel"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={modalLoading}
                  className="coupon-v3-modal-submit"
                >
                  {modalLoading ? "Saving..." : editingCoupon ? "Save Changes" : "Create Coupon"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
