import crypto from "node:crypto";
import { Cashfree, CFEnvironment } from "cashfree-pg";

export function getCashfreeClientId(): string {
  return (
    process.env.CASHFREE_CLIENT_ID ||
    process.env.CASHFREE_APP_ID ||
    process.env.NEXT_PUBLIC_CASHFREE_CLIENT_ID ||
    process.env.NEXT_PUBLIC_CASHFREE_APP_ID ||
    ""
  );
}

export function getCashfreeClientSecret(): string {
  return (
    process.env.CASHFREE_CLIENT_SECRET ||
    process.env.CASHFREE_SECRET_KEY ||
    ""
  );
}

export function getCashfreeEnvironment(): CFEnvironment {
  const env = (
    process.env.CASHFREE_ENVIRONMENT ||
    process.env.NEXT_PUBLIC_CASHFREE_ENVIRONMENT ||
    ""
  ).toUpperCase();
  if (env === "PRODUCTION") return CFEnvironment.PRODUCTION;
  if (env === "SANDBOX") return CFEnvironment.SANDBOX;

  const secret = getCashfreeClientSecret();
  if (secret.includes("_prod_")) {
    return CFEnvironment.PRODUCTION;
  }
  return CFEnvironment.PRODUCTION;
}

export function isCashfreeConfigured(): boolean {
  return Boolean(getCashfreeClientId() && getCashfreeClientSecret());
}

let cachedClient: Cashfree | null = null;
let cachedConfigKey = "";

export function getCashfreeClient(): Cashfree | null {
  const clientId = getCashfreeClientId();
  const clientSecret = getCashfreeClientSecret();
  const env = getCashfreeEnvironment();

  if (!clientId || !clientSecret) return null;

  const configKey = `${env}:${clientId}:${clientSecret}`;
  if (cachedClient && cachedConfigKey === configKey) {
    return cachedClient;
  }

  cachedClient = new Cashfree(env, clientId, clientSecret);
  cachedConfigKey = configKey;
  return cachedClient;
}

export type CreatedCashfreeOrder = {
  orderId: string;
  paymentSessionId: string;
  cfOrderId?: string;
  orderAmount: number;
  orderCurrency: string;
  isMock: boolean;
};

export async function createCashfreeOrder(params: {
  orderId: string;
  orderAmount: number; // in Rupees (e.g. 500 for ₹500.00)
  customerPhone: string;
  customerName?: string;
  customerEmail?: string;
  orderNote?: string;
  returnUrl?: string;
  notifyUrl?: string;
}): Promise<CreatedCashfreeOrder> {
  const client = getCashfreeClient();

  // Normalize phone (Cashfree requires valid phone, strip +91 or non-digits, ensure 10 digits or international format)
  const rawPhone = params.customerPhone.replace(/\D/g, "");
  const phone = rawPhone.length >= 10 ? rawPhone.slice(-10) : "9876543210";
  const customerId = `cust_${phone.slice(-6)}_${Date.now().toString(36)}`;

  if (client) {
    const response = await client.PGCreateOrder({
      order_id: params.orderId,
      order_amount: Number(params.orderAmount.toFixed(2)),
      order_currency: "INR",
      customer_details: {
        customer_id: customerId,
        customer_phone: phone,
        customer_name: params.customerName || "Customer",
        customer_email: params.customerEmail || "customer@example.com",
      },
      order_meta: {
        return_url: params.returnUrl,
        notify_url: params.notifyUrl,
      },
      order_note: params.orderNote,
    });

    const data = response.data;
    if (!data.payment_session_id) {
      throw new Error("Cashfree failed to return payment_session_id");
    }

    return {
      orderId: data.order_id || params.orderId,
      paymentSessionId: data.payment_session_id,
      cfOrderId: data.cf_order_id,
      orderAmount: data.order_amount ?? params.orderAmount,
      orderCurrency: data.order_currency || "INR",
      isMock: false,
    };
  }

  // Fallback for local development or testing when Cashfree API credentials are not set
  const mockSessionId = `session_sim_${Date.now().toString(36)}_${crypto.randomBytes(6).toString("hex")}`;
  return {
    orderId: params.orderId,
    paymentSessionId: mockSessionId,
    cfOrderId: `cf_sim_${Date.now()}`,
    orderAmount: params.orderAmount,
    orderCurrency: "INR",
    isMock: true,
  };
}

export async function fetchCashfreeOrder(orderId: string) {
  if (orderId.startsWith("order_sim_") || orderId.startsWith("sim_")) {
    return {
      order_id: orderId,
      order_status: "PAID",
      order_amount: 0,
      order_currency: "INR",
      isMock: true,
    };
  }

  const client = getCashfreeClient();
  if (!client) {
    throw new Error("Cashfree credentials are not configured.");
  }

  const response = await client.PGFetchOrder(orderId);
  return response.data;
}

export function verifyCashfreeWebhook(signature: string, rawBody: string, timestamp: string) {
  const client = getCashfreeClient();
  if (!client) {
    throw new Error("Cashfree credentials are not configured.");
  }

  return client.PGVerifyWebhookSignature(signature, rawBody, timestamp);
}
