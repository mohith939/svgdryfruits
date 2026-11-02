"use client";

import { useState } from "react";
import { launchCashfreeCheckout } from "@/lib/cashfree-client";

interface CashfreeCheckoutButtonProps {
  amountRupees?: number;
  amountPaise?: number; // e.g. 50000 for ₹500.00
  currency?: string;
  receipt?: string;
  orderId?: string;
  customer?: {
    name?: string;
    email?: string;
    contact?: string;
  };
  onSuccess?: (data: { order_id: string; payment_id: string }) => void;
  onFailure?: (error: string) => void;
  className?: string;
  children?: React.ReactNode;
}

export function CashfreeCheckoutButton({
  amountRupees,
  amountPaise,
  currency = "INR",
  receipt,
  orderId,
  customer = {
    name: "Customer",
    email: "customer@example.com",
    contact: "9876543210",
  },
  onSuccess,
  onFailure,
  className = "button button-primary",
  children,
}: CashfreeCheckoutButtonProps) {
  const [loading, setLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [successInfo, setSuccessInfo] = useState<{ payment_id: string; order_id: string } | null>(null);

  const finalAmountRupees = amountRupees ?? (amountPaise ? amountPaise / 100 : 500);

  async function handlePayment() {
    setLoading(true);
    setErrorMessage(null);
    setSuccessInfo(null);

    try {
      // Step 1: Create Order via Backend API to obtain payment_session_id
      const createRes = await fetch("/api/create-order", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          order_amount: finalAmountRupees,
          currency,
          order_id: orderId || receipt || `cf_test_${Date.now()}`,
          customer_name: customer.name,
          customer_email: customer.email,
          customer_phone: customer.contact,
        }),
      });

      const orderData = await createRes.json();
      if (!createRes.ok) {
        throw new Error(orderData.error || "Failed to create Cashfree order");
      }

      const { order_id, payment_session_id, is_mock } = orderData;

      if (is_mock) {
        // Dev/simulated environment
        const verifyRes = await fetch("/api/verify-payment", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            order_id,
            is_mock: true,
          }),
        });
        const verifyData = await verifyRes.json();
        setSuccessInfo({ order_id, payment_id: verifyData.payment_id || "cf_sim_payment" });
        onSuccess?.({ order_id, payment_id: verifyData.payment_id || "cf_sim_payment" });
        return;
      }

      // Step 2: Launch Cashfree Modal Checkout
      const result = await launchCashfreeCheckout({
        paymentSessionId: payment_session_id,
        redirectTarget: "_modal",
      });

      if (result?.error) {
        throw new Error(result.error.message || "Payment cancelled or failed");
      }

      // Step 3: Verify Payment via Backend API
      const verifyRes = await fetch("/api/verify-payment", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          order_id,
          payment_id: result?.paymentDetails?.paymentId,
        }),
      });

      const verifyData = await verifyRes.json();
      if (!verifyRes.ok || !verifyData.success) {
        throw new Error(verifyData.error || "Payment verification failed");
      }

      setSuccessInfo({
        order_id,
        payment_id: verifyData.payment_id || result?.paymentDetails?.paymentId,
      });

      onSuccess?.({
        order_id,
        payment_id: verifyData.payment_id || result?.paymentDetails?.paymentId,
      });
    } catch (err: any) {
      console.error("Cashfree checkout error:", err);
      const msg = err.message || "An error occurred while launching payment";
      setErrorMessage(msg);
      onFailure?.(msg);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="cashfree-checkout-wrapper" style={{ display: "inline-block" }}>
      <button
        type="button"
        onClick={handlePayment}
        disabled={loading}
        className={className}
      >
        {loading ? "Processing..." : children || `Pay ₹${finalAmountRupees.toFixed(2)} with Cashfree`}
      </button>

      {errorMessage && (
        <p style={{ color: "#b91c1c", fontSize: 13, marginTop: 8 }}>
          {errorMessage}
        </p>
      )}

      {successInfo && (
        <p style={{ color: "#15803d", fontSize: 13, marginTop: 8 }}>
          Payment verified successfully! ID: {successInfo.payment_id}
        </p>
      )}
    </div>
  );
}
