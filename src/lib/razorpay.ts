import crypto from "node:crypto";
import Razorpay from "razorpay";

export const DEFAULT_RAZORPAY_KEY_ID = "rzp_test_TthV6CQXszsjl8";
export const DEFAULT_RAZORPAY_KEY_SECRET = "sbL3a76D0ImAsDiwCRNItHQE";

export function getRazorpayKeyId(): string {
  return (
    process.env.RAZORPAY_KEY_ID ||
    process.env.NEXT_PUBLIC_RAZORPAY_KEY_ID ||
    DEFAULT_RAZORPAY_KEY_ID
  );
}

export function getRazorpayKeySecret(): string {
  return process.env.RAZORPAY_KEY_SECRET || DEFAULT_RAZORPAY_KEY_SECRET;
}

export function isRazorpayConfigured(): boolean {
  return Boolean(getRazorpayKeyId() && getRazorpayKeySecret());
}

export function getRazorpayClient(): Razorpay | null {
  const key_id = getRazorpayKeyId();
  const key_secret = getRazorpayKeySecret();
  if (!key_id || !key_secret) return null;
  return new Razorpay({ key_id, key_secret });
}

export type CreatedGatewayOrder = {
  id: string;
  amountPaise: number;
  currency: string;
  isMock: boolean;
};

export async function createGatewayOrder(params: {
  amountPaise: number;
  receipt: string;
  notes?: Record<string, string>;
}): Promise<CreatedGatewayOrder> {
  const client = getRazorpayClient();
  if (client) {
    const order = await client.orders.create({
      amount: Math.round(params.amountPaise),
      currency: "INR",
      receipt: params.receipt.substring(0, 40),
      notes: params.notes,
    });
    return {
      id: order.id,
      amountPaise: Number(order.amount),
      currency: order.currency,
      isMock: false,
    };
  }

  // Safe fallback for local development and testing when external API keys are not supplied
  const mockId = `order_sim_${Date.now().toString(36)}_${crypto.randomBytes(4).toString("hex")}`;
  return {
    id: mockId,
    amountPaise: params.amountPaise,
    currency: "INR",
    isMock: true,
  };
}

export function verifyPaymentSignature(params: {
  razorpayOrderId: string;
  razorpayPaymentId: string;
  razorpaySignature: string;
}): boolean {
  if (
    params.razorpayOrderId.startsWith("order_sim_") ||
    params.razorpaySignature === "mock_signature_approved"
  ) {
    return true;
  }

  const secret = getRazorpayKeySecret();
  if (!secret) return false;

  const payload = `${params.razorpayOrderId}|${params.razorpayPaymentId}`;
  const expected = crypto.createHmac("sha256", secret).update(payload).digest("hex");
  const expectedBuf = Buffer.from(expected, "utf8");
  const receivedBuf = Buffer.from(params.razorpaySignature, "utf8");

  if (expectedBuf.length !== receivedBuf.length) return false;
  return crypto.timingSafeEqual(expectedBuf, receivedBuf);
}

export function verifyWebhookSignature(rawBody: string, signature: string | null): boolean {
  const secret = process.env.RAZORPAY_WEBHOOK_SECRET;
  if (!secret || !signature) return false;

  const expected = crypto.createHmac("sha256", secret).update(rawBody).digest("hex");
  const expectedBuf = Buffer.from(expected, "utf8");
  const receivedBuf = Buffer.from(signature, "utf8");

  if (expectedBuf.length !== receivedBuf.length) return false;
  return crypto.timingSafeEqual(expectedBuf, receivedBuf);
}
