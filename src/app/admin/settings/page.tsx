"use client";

import { useEffect, useState } from "react";
import { formatINR } from "@/lib/money";

type SettingsData = {
  shipping: {
    flatChargePaise: number;
    freeThresholdPaise: number | null;
    active: boolean;
  };
  env: {
    siteUrl: string;
    whatsappNumber: string;
    contactEmail: string;
    razorpayKeyConfigured: boolean;
  };
};

export default function AdminSettingsPage() {
  const [data, setData] = useState<SettingsData | null>(null);
  const [loading, setLoading] = useState(true);
  const [flatINR, setFlatINR] = useState("0");
  const [freeINR, setFreeINR] = useState("");
  const [active, setActive] = useState(false);
  const [saving, setSaving] = useState(false);
  const [msg, setMsg] = useState<string | null>(null);

  useEffect(() => {
    fetch("/api/admin/settings")
      .then((r) => r.json())
      .then((d) => {
        if (d.shipping) {
          setData(d);
          setFlatINR(String(d.shipping.flatChargePaise / 100));
          setFreeINR(d.shipping.freeThresholdPaise ? String(d.shipping.freeThresholdPaise / 100) : "");
          setActive(d.shipping.active);
        }
        setLoading(false);
      })
      .catch(() => setLoading(false));
  }, []);

  async function handleSaveShipping(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setMsg(null);

    try {
      const res = await fetch("/api/admin/settings", {
        method: "PUT",
        headers: { "content-type": "application/json" },
        body: JSON.stringify({
          flatChargePaise: Math.round(Number(flatINR) * 100),
          freeThresholdPaise: freeINR ? Math.round(Number(freeINR) * 100) : null,
          active,
        }),
      });

      if (res.ok) {
        setMsg("Shipping settings updated successfully.");
      } else {
        setMsg("Failed to save shipping settings.");
      }
    } catch {
      setMsg("Network error.");
    } finally {
      setSaving(false);
    }
  }

  if (loading) return <div className="admin-content"><p>Loading settings…</p></div>;
  if (!data) return <div className="admin-content"><p>Failed to load settings.</p></div>;

  return (
    <div className="admin-content">
      <div className="admin-page-header">
        <div>
          <h1>Store &amp; <em>Shipping Settings</em></h1>
          <p>Configure delivery fees, free-shipping thresholds, and inspect environment integration.</p>
        </div>
      </div>

      {msg && <div className="admin-info-banner">{msg}</div>}

      <div className="admin-two-cols">
        {/* Shipping Form */}
        <div className="admin-card">
          <h2>Shipping Rules</h2>
          <form className="admin-form" onSubmit={handleSaveShipping}>
            <div className="form-checkbox-row">
              <label className="checkbox-label">
                <input
                  type="checkbox"
                  checked={active}
                  onChange={(e) => setActive(e.target.checked)}
                />
                <span><strong>Enable shipping charge calculation</strong></span>
              </label>
            </div>
            <p className="admin-card-subtitle">
              When disabled, shipping is calculated as Free for all orders.
            </p>

            <div className="form-group">
              <label htmlFor="flat">Standard Flat Delivery Fee (₹)</label>
              <input
                id="flat"
                type="number"
                min="0"
                value={flatINR}
                onChange={(e) => setFlatINR(e.target.value)}
              />
            </div>

            <div className="form-group">
              <label htmlFor="free">Free Shipping Threshold (₹, optional)</label>
              <input
                id="free"
                type="number"
                min="0"
                value={freeINR}
                onChange={(e) => setFreeINR(e.target.value)}
                placeholder="e.g. 999 (Orders above this qualify for free shipping)"
              />
              <span className="field-hint">
                Orders with subtotal above this amount get free shipping automatically, and a progress bar appears in the customer bag.
              </span>
            </div>

            <button type="submit" className="button button-primary" disabled={saving}>
              {saving ? "Saving…" : "Save Shipping Rules"}
            </button>
          </form>
        </div>

        {/* Integration Status Box */}
        <div className="admin-card">
          <h2>Integration &amp; Environment Status</h2>
          <div className="admin-details-grid">
            <div>
              <span className="admin-detail-label">Razorpay Gateway Keys</span>
              <span className={`badge ${data.env.razorpayKeyConfigured ? "badge-success" : "badge-warning"}`}>
                {data.env.razorpayKeyConfigured ? "Production/Test Keys Configured" : "Simulated Test Mode"}
              </span>
            </div>
            <div>
              <span className="admin-detail-label">Storefront Base URL</span>
              <code>{data.env.siteUrl}</code>
            </div>
            <div>
              <span className="admin-detail-label">Support WhatsApp Number</span>
              <span>{data.env.whatsappNumber || "Not configured (set NEXT_PUBLIC_WHATSAPP_NUMBER in .env)"}</span>
            </div>
            <div>
              <span className="admin-detail-label">Contact Email</span>
              <span>{data.env.contactEmail || "Not configured (set NEXT_PUBLIC_CONTACT_EMAIL in .env)"}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
