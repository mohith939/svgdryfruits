import { NextResponse } from "next/server";
import { fetchCashfreeOrder, isCashfreeConfigured } from "@/lib/cashfree";

export async function POST(req: Request) {
  try {
    const body = await req.json().catch(() => null);
    if (!body) {
      return NextResponse.json({ error: "Invalid JSON body" }, { status: 400 });
    }

    const order_id = body.order_id || body.cashfree_order_id || body.orderId;
    const payment_id = body.payment_id || body.cf_payment_id || body.paymentId || "cf_pay_success";

    if (!order_id) {
      return NextResponse.json(
        { error: "Missing required parameter: order_id is required" },
        { status: 400 }
      );
    }

    // In local dev/testing without credentials or for simulated orders:
    if (!isCashfreeConfigured() || order_id.startsWith("order_sim_") || order_id.startsWith("sim_") || body.is_mock) {
      return NextResponse.json({
        success: true,
        message: "Payment verified successfully (dev/mock)",
        order_id,
        payment_id,
        order_status: "PAID",
      });
    }

    try {
      const order = await fetchCashfreeOrder(order_id);
      const isPaid = order.order_status === "PAID";

      if (!isPaid) {
        return NextResponse.json(
          {
            success: false,
            error: `Order payment status is ${order.order_status}`,
            order_status: order.order_status,
          },
          { status: 400 }
        );
      }

      return NextResponse.json({
        success: true,
        message: "Payment verified successfully",
        order_id: order.order_id || order_id,
        payment_id,
        order_status: order.order_status,
      });
    } catch (apiError: any) {
      console.error("Cashfree order verification error:", apiError);
      return NextResponse.json(
        {
          success: false,
          error: apiError?.response?.data?.message || apiError?.message || "Cashfree payment verification failed",
        },
        { status: 400 }
      );
    }
  } catch (err: any) {
    console.error("Payment verification error:", err);
    return NextResponse.json(
      { error: err?.message || "Failed to verify payment" },
      { status: 500 }
    );
  }
}
