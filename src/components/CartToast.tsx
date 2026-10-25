"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { AnimatePresence, motion } from "motion/react";
import { Icon } from "./Icon";

export interface CartToastPayload {
  title: string;
  size?: string;
  qty?: number;
}

export function triggerCartToast(payload: CartToastPayload) {
  if (typeof window !== "undefined") {
    window.dispatchEvent(new CustomEvent("svg:cart-add", { detail: payload }));
  }
}

export function CartToast() {
  const [toast, setToast] = useState<(CartToastPayload & { id: number }) | null>(null);

  useEffect(() => {
    function handleCartAdd(e: Event) {
      const customEvent = e as CustomEvent<CartToastPayload>;
      if (customEvent.detail) {
        setToast({
          ...customEvent.detail,
          id: Date.now(),
        });
      }
    }

    window.addEventListener("svg:cart-add", handleCartAdd);
    return () => window.removeEventListener("svg:cart-add", handleCartAdd);
  }, []);

  useEffect(() => {
    if (!toast) return;
    const timer = setTimeout(() => {
      setToast(null);
    }, 4500);
    return () => clearTimeout(timer);
  }, [toast]);

  return (
    <div className="cart-toast-container" aria-live="polite" aria-atomic="true">
      <AnimatePresence>
        {toast && (
          <motion.div
            key={toast.id}
            className="cart-toast-card"
            initial={{ opacity: 0, y: -24, scale: 0.94 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: -16, scale: 0.94 }}
            transition={{ type: "spring", stiffness: 420, damping: 30 }}
          >
            <div className="cart-toast-icon-wrap" aria-hidden="true">
              <Icon name="bag" size={20} />
            </div>

            <div className="cart-toast-content">
              <div className="cart-toast-header">
                <strong>Product added to bag!</strong>
                <span className="cart-toast-badge">In Bag</span>
              </div>
              <p className="cart-toast-title">
                {toast.title}
                {toast.size ? ` · ${toast.size}` : ""}
                {toast.qty && toast.qty > 1 ? ` (×${toast.qty})` : ""}
              </p>
              <div className="cart-toast-actions">
                <Link href="/cart" className="cart-toast-cta" onClick={() => setToast(null)}>
                  View Bag &amp; Checkout <span>→</span>
                </Link>
              </div>
            </div>

            <button
              type="button"
              className="cart-toast-close"
              aria-label="Dismiss notification"
              onClick={() => setToast(null)}
            >
              ×
            </button>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}
