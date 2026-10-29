"use client";

import { useState } from "react";
import Link from "next/link";
import { RazorpayCheckoutButton } from "@/components/RazorpayCheckoutButton";

export default function RazorpayTestPage() {
  const [amountRupees, setAmountRupees] = useState(100); // ₹100 default
  const [logs, setLogs] = useState<string[]>([]);

  const addLog = (msg: string) => {
    setLogs((prev) => [`[${new Date().toLocaleTimeString()}] ${msg}`, ...prev]);
  };

  return (
    <div style={{ maxWidth: 760, margin: "40px auto", padding: "0 20px", fontFamily: "sans-serif" }}>
      <div style={{ marginBottom: 24 }}>
        <Link href="/" style={{ color: "#143D24", textDecoration: "underline", fontSize: 14 }}>
          ← Back to SVG Dry Fruits Store
        </Link>
      </div>

      <div style={{ background: "#fff", border: "1px solid #e5e7eb", borderRadius: 12, padding: 32, boxShadow: "0 4px 12px rgba(0,0,0,0.05)" }}>
        <h1 style={{ fontSize: 24, fontWeight: 700, color: "#143D24", marginTop: 0 }}>
          Razorpay Standard Checkout Integration
        </h1>
        <p style={{ color: "#4b5563", fontSize: 15, lineHeight: 1.6 }}>
          This page tests the end-to-end integration of Razorpay Standard Web Checkout:
          backend order creation (<code>/api/create-order</code>), the checkout modal, and backend signature verification (<code>/api/verify-payment</code>).
        </p>

        <div style={{ background: "#f3f4f6", padding: 16, borderRadius: 8, margin: "20px 0" }}>
          <p style={{ margin: 0, fontSize: 14, color: "#374151" }}>
            <strong>Configured Key ID:</strong> <code>rzp_test_TthV6CQXszsjl8</code>
          </p>
          <p style={{ margin: "6px 0 0", fontSize: 13, color: "#6b7280" }}>
            Test Mode Active: You can use Razorpay test cards or UPI to simulate payments.
          </p>
        </div>

        <div style={{ marginBottom: 24 }}>
          <label style={{ display: "block", fontSize: 14, fontWeight: 600, color: "#1f2937", marginBottom: 8 }}>
            Payment Amount (INR):
          </label>
          <div style={{ display: "flex", gap: 12, alignItems: "center" }}>
            <input
              type="number"
              min="1"
              value={amountRupees}
              onChange={(e) => setAmountRupees(Math.max(1, Number(e.target.value)))}
              style={{
                padding: "10px 14px",
                border: "1px solid #d1d5db",
                borderRadius: 6,
                fontSize: 16,
                width: 140,
              }}
            />
            <span style={{ fontSize: 14, color: "#6b7280" }}>
              = {(amountRupees * 100).toLocaleString()} paise
            </span>
          </div>

          <div style={{ display: "flex", gap: 8, marginTop: 10 }}>
            {[1, 50, 100, 500].map((val) => (
              <button
                key={val}
                type="button"
                onClick={() => setAmountRupees(val)}
                style={{
                  background: amountRupees === val ? "#143D24" : "#f3f4f6",
                  color: amountRupees === val ? "#fff" : "#374151",
                  border: "none",
                  borderRadius: 4,
                  padding: "4px 10px",
                  fontSize: 12,
                  cursor: "pointer",
                }}
              >
                ₹{val}
              </button>
            ))}
          </div>
        </div>

        <div style={{ marginTop: 24 }}>
          <RazorpayCheckoutButton
            amountPaise={amountRupees * 100}
            name="SVG Dry Fruits Test"
            description={`Test Payment of ₹${amountRupees}`}
            customer={{
              name: "Mohith",
              email: "test@svgdryfruits.com",
              contact: "9876543210",
            }}
            onSuccess={(data) => {
              addLog(`Payment SUCCESS! Payment ID: ${data.payment_id}, Order ID: ${data.order_id}`);
            }}
            onFailure={(err) => {
              addLog(`Payment FAILED/ERROR: ${err}`);
            }}
            className="button button-primary"
          >
            Pay ₹{amountRupees} with Razorpay
          </RazorpayCheckoutButton>
        </div>

        {logs.length > 0 && (
          <div style={{ marginTop: 28, borderTop: "1px solid #e5e7eb", paddingTop: 20 }}>
            <h3 style={{ fontSize: 14, textTransform: "uppercase", letterSpacing: "0.05em", color: "#6b7280" }}>
              Activity Log
            </h3>
            <div style={{ background: "#111827", color: "#10b981", padding: 14, borderRadius: 6, fontSize: 13, maxHeight: 180, overflowY: "auto", fontFamily: "monospace" }}>
              {logs.map((log, i) => (
                <div key={i}>{log}</div>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
