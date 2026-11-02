"use client";

import { load } from "@cashfreepayments/cashfree-js";

let cachedInstances: Record<string, Promise<any>> = {};

export function getCashfreeClientInstance(mode?: "production" | "sandbox" | string | number) {
  if (typeof window === "undefined") return null;

  const isSandbox =
    mode === "sandbox" ||
    mode === 1 ||
    String(mode || "").toLowerCase() === "sandbox" ||
    (process.env.NEXT_PUBLIC_CASHFREE_ENVIRONMENT || "").toLowerCase() === "sandbox";

  const targetMode: "sandbox" | "production" = isSandbox ? "sandbox" : "production";

  if (!cachedInstances[targetMode]) {
    cachedInstances[targetMode] = load({ mode: targetMode });
  }

  return cachedInstances[targetMode];
}

export type CashfreeCheckoutOptions = {
  paymentSessionId: string;
  redirectTarget?: "_modal" | "_self" | "_blank";
  mode?: "production" | "sandbox";
};

export async function launchCashfreeCheckout(options: CashfreeCheckoutOptions) {
  const cashfree = await getCashfreeClientInstance(options.mode);
  if (!cashfree) {
    throw new Error("Unable to initialize Cashfree Web SDK. Please check your internet connection.");
  }

  return cashfree.checkout({
    paymentSessionId: options.paymentSessionId,
    redirectTarget: options.redirectTarget || "_modal",
  });
}
