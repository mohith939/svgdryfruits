"use client";

import { useState } from "react";
import Script from "next/script";

interface RazorpayCheckoutButtonProps {
  amountPaise?: number; // e.g. 50000 for ₹500.00
  currency?: string;
  receipt?: string;
  name?: string;
  description?: string;
  customer?: {
    name?: string;
    email?: string;
    contact?: string;
  };
  onSuccess?: (data: { order_id: string; payment_id: string; razorpay_signature: string }) => void;
  onFailure?: (error: string) => void;
  className?: string;
  children?: React.ReactNode;
}

export function RazorpayCheckoutButton({
  amountPaise = 50000, // Default ₹500.00 (50,000 paise)
  currency = "INR",
  receipt,
  name = "SVG Dry Fruits",
  description = "Premium Dry Fruits & Nuts",
  customer = {
    name: "Customer",
    email: "customer@example.com",
    contact: "9876543210",
  },
  onSuccess,
  onFailure,
  className = "button button-primary",
  children,
}: RazorpayCheckoutButtonProps) {
  const [loading, setLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [successInfo, setSuccessInfo] = useState<{ payment_id: string; order_id: string } | null>(null);

  async function handlePayment() {
    setLoading(true);
    setErrorMessage(null);
    setSuccessInfo(null);

    try {
      // Step 1: Create Order via Backend API
      const createRes = await fetch("/api/create-order", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          amount: amountPaise,
          currency,
          receipt: receipt || `rcpt_${Date.now()}`,
        }),
      });

      const orderData = await createRes.json();
      if (!createRes.ok) {
        throw new Error(orderData.error || "Failed to create Razorpay order");
      }

      const { order_id, amount } = orderData;
      const keyId = process.env.NEXT_PUBLIC_RAZORPAY_KEY_ID || "rzp_test_TthV6CQXszsjl8";

      // Step 2: Open Razorpay Standard Checkout Modal
      if (typeof window === "undefined" || !(window as any).Razorpay) {
        throw new Error("Razorpay SDK not loaded yet. Please try again.");
      }

      const RazorpayClass = (window as any).Razorpay;
      const rzp = new RazorpayClass({
        key: keyId,
        amount,
        currency,
        name,
        description,
        image: "/assets/svg-dry-fruits-logo.jpg",
        order_id,
        handler: async function (response: {
          razorpay_order_id: string;
          razorpay_payment_id: string;
          razorpay_signature: string;
        }) {
          try {
            // Step 3: Verify Payment Signature via Backend API
            const verifyRes = await fetch("/api/verify-payment", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({
                order_id: response.razorpay_order_id,
                payment_id: response.razorpay_payment_id,
                razorpay_signature: response.razorpay_signature,
              }),
            });

            const verifyData = await verifyRes.json();
            if (!verifyRes.ok || !verifyData.success) {
              throw new Error(verifyData.error || "Payment signature verification failed");
            }

            setSuccessInfo({
              order_id: response.razorpay_order_id,
              payment_id: response.razorpay_payment_id,
            });

            onSuccess?.({
              order_id: response.razorpay_order_id,
              payment_id: response.razorpay_payment_id,
              razorpay_signature: response.razorpay_signature,
            });
          } catch (verifyErr: any) {
            const errStr = verifyErr.message || "Payment verification failed";
            setErrorMessage(errStr);
            onFailure?.(errStr);
          } finally {
            setLoading(false);
          }
        },
        prefill: {
          name: customer.name,
          email: customer.email,
          contact: customer.contact,
        },
        theme: {
          color: "#203c32",
        },
        modal: {
          ondismiss: function () {
            setLoading(false);
          },
        },
      });

      rzp.on("payment.failed", function (failResponse: any) {
        setLoading(false);
        const reason =
          failResponse?.error?.description ||
          failResponse?.error?.reason ||
          "Payment was declined or failed. Please try again.";
        setErrorMessage(reason);
        onFailure?.(reason);
      });

      rzp.open();
    } catch (err: any) {
      console.error("Payment initiation error:", err);
      const msg = err.message || "An error occurred while launching payment";
      setErrorMessage(msg);
      onFailure?.(msg);
      setLoading(false);
    }
  }

  return (
    <div className="razorpay-checkout-wrapper" style={{ display: "inline-block" }}>
      <Script src="https://checkout.razorpay.com/v1/checkout.js" strategy="lazyOnload" />

      <button
        type="button"
        onClick={handlePayment}
        disabled={loading}
        className={className}
      >
        {loading ? "Processing..." : children || `Pay ₹${(amountPaise / 100).toFixed(2)} with Razorpay`}
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
