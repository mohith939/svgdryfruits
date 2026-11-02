declare module "@cashfreepayments/cashfree-js" {
  export interface CashfreeInstance {
    checkout(options: {
      paymentSessionId: string;
      redirectTarget?: "_modal" | "_self" | "_blank";
    }): Promise<{
      error?: { message?: string; code?: string };
      redirect?: boolean;
      paymentDetails?: { paymentId?: string; [key: string]: any };
    }>;
  }

  export function load(options: { mode: "sandbox" | "production" }): Promise<CashfreeInstance>;
}
