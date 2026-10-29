"use client";

/**
 * Dynamically loads the Razorpay checkout.js script if not already present.
 * Resolves to true when window.Razorpay is available, false if loading failed.
 */
export function loadRazorpayScript(): Promise<boolean> {
  return new Promise((resolve) => {
    if (typeof window === "undefined") {
      resolve(false);
      return;
    }

    if ((window as any).Razorpay) {
      resolve(true);
      return;
    }

    const existing = document.querySelector<HTMLScriptElement>(
      'script[src="https://checkout.razorpay.com/v1/checkout.js"]'
    );

    if (existing) {
      if ((window as any).Razorpay) {
        resolve(true);
        return;
      }
      existing.addEventListener("load", () => resolve(Boolean((window as any).Razorpay)));
      existing.addEventListener("error", () => resolve(false));
      setTimeout(() => resolve(Boolean((window as any).Razorpay)), 2500);
      return;
    }

    const script = document.createElement("script");
    script.src = "https://checkout.razorpay.com/v1/checkout.js";
    script.async = true;
    script.onload = () => resolve(Boolean((window as any).Razorpay));
    script.onerror = () => resolve(false);
    document.body.appendChild(script);
  });
}
