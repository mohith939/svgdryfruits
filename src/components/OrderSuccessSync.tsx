"use client";

import { useEffect } from "react";
import { clearCart } from "@/lib/cart";

export function OrderSuccessSync() {
  useEffect(() => {
    try {
      clearCart();
      sessionStorage.removeItem("svg-coupon-v1");
      sessionStorage.removeItem("svg_applied_coupon");
    } catch (err) {
      console.error("Failed to clear cart on success:", err);
    }
  }, []);

  return null;
}
