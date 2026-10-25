import crypto from "node:crypto";
import Razorpay from "razorpay";

export function isRazorpayConfigured(): boolean {
  return Boolean(process.env.RAZORPAY_KEY_ID && process.env.RAZORPAY_KEY_SECRET);
}

export function getRazorpayClient(): Razorpay | null {
  const key_id = process.env.RAZORPAY_KEY_ID;
  const key_secret = process.env.RAZORPAY_KEY_SECRET;
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
      amount: params.amountPaise,
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
  const secret = process.env.RAZORPAY_KEY_SECRET;
  if (!secret) {
    // If running in development without credentials, allow simulated signature or mock order
    if (params.razorpayOrderId.startsWith("order_sim_") || params.razorpaySignature === "mock_signature_approved") {
      return true;
    }
    return false;
  }

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
