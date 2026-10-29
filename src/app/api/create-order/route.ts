import { NextResponse } from "next/server";
import Razorpay from "razorpay";
import { getRazorpayKeyId, getRazorpayKeySecret } from "@/lib/razorpay";

export async function POST(req: Request) {
  try {
    const body = await req.json().catch(() => null);
    if (!body) {
      return NextResponse.json({ error: "Invalid JSON body" }, { status: 400 });
    }

    const amount = Number(body.amount);
    if (!Number.isFinite(amount) || amount < 100) {
      return NextResponse.json(
        { error: "Invalid amount. Minimum amount is 100 paise (₹1.00)." },
        { status: 400 }
      );
    }

    const key_id = getRazorpayKeyId();
    const key_secret = getRazorpayKeySecret();

    if (!key_id || !key_secret) {
      return NextResponse.json(
        { error: "Razorpay credentials not configured. Please set RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET." },
        { status: 401 }
      );
    }

    const currency = (body.currency || "INR").toUpperCase();
    const receipt = (body.receipt || `rcpt_${Date.now()}`).substring(0, 40);

    const razorpay = new Razorpay({ key_id, key_secret });

    try {
      const order = await razorpay.orders.create({
        amount: Math.round(amount),
        currency,
        receipt,
        notes: body.notes,
      });

      return NextResponse.json({
        order_id: order.id,
        amount: order.amount,
        currency: order.currency,
        key_id,
      });
    } catch (apiError: any) {
      console.error("Razorpay order creation error:", apiError);
      const statusCode = apiError?.statusCode || apiError?.status;
      if (statusCode === 401 || (apiError?.error?.code === "BAD_REQUEST_ERROR" && apiError?.error?.description?.toLowerCase().includes("auth"))) {
        return NextResponse.json(
          { error: apiError?.error?.description || "Authentication failure with Razorpay" },
          { status: 401 }
        );
      }
      return NextResponse.json(
        { error: apiError?.error?.description || apiError?.message || "Razorpay API error" },
        { status: statusCode === 400 ? 400 : 500 }
      );
    }
  } catch (err: any) {
    console.error("Error creating order:", err);
    return NextResponse.json(
      { error: err?.message || "Internal server error" },
      { status: 500 }
    );
  }
}
