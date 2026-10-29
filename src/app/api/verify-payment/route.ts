import { NextResponse } from "next/server";
import crypto from "node:crypto";
import { getRazorpayKeySecret } from "@/lib/razorpay";

export async function POST(req: Request) {
  try {
    const body = await req.json().catch(() => null);
    if (!body) {
      return NextResponse.json({ error: "Invalid JSON body" }, { status: 400 });
    }

    // Accept both order_id / razorpay_order_id, payment_id / razorpay_payment_id
    const order_id = body.order_id || body.razorpay_order_id;
    const payment_id = body.payment_id || body.razorpay_payment_id;
    const signature = body.razorpay_signature || body.signature;

    if (!order_id || !payment_id || !signature) {
      return NextResponse.json(
        { error: "Missing required fields: order_id, payment_id, and razorpay_signature are required" },
        { status: 400 }
      );
    }

    const key_secret = getRazorpayKeySecret();
    if (!key_secret) {
      return NextResponse.json(
        { error: "Razorpay secret key not configured" },
        { status: 500 }
      );
    }

    // Algorithm: HMAC-SHA256(order_id + "|" + payment_id, KEY_SECRET)
    const payload = `${order_id}|${payment_id}`;
    const generatedSignature = crypto
      .createHmac("sha256", key_secret)
      .update(payload)
      .digest("hex");

    const generatedBuf = Buffer.from(generatedSignature, "utf8");
    const signatureBuf = Buffer.from(signature, "utf8");

    const isValid =
      generatedBuf.length === signatureBuf.length &&
      crypto.timingSafeEqual(generatedBuf, signatureBuf);

    if (!isValid) {
      return NextResponse.json(
        { success: false, error: "Invalid payment signature. Verification failed." },
        { status: 400 }
      );
    }

    return NextResponse.json({
      success: true,
      message: "Payment verified successfully",
      order_id,
      payment_id,
    });
  } catch (err: any) {
    console.error("Payment verification error:", err);
    return NextResponse.json(
      { error: err?.message || "Failed to verify payment" },
      { status: 500 }
    );
  }
}
