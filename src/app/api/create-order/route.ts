import { NextResponse } from "next/server";
import { createCashfreeOrder, getCashfreeClientId, getCashfreeClientSecret } from "@/lib/cashfree";

export async function POST(req: Request) {
  try {
    const body = await req.json().catch(() => null);
    if (!body) {
      return NextResponse.json({ error: "Invalid JSON body" }, { status: 400 });
    }

    // Amount can be supplied as `amount` (paise) or `order_amount` (rupees)
    let amountInRupees: number;
    if (typeof body.order_amount === "number") {
      amountInRupees = body.order_amount;
    } else {
      const rawAmount = Number(body.amount);
      if (!Number.isFinite(rawAmount) || rawAmount <= 0) {
        return NextResponse.json(
          { error: "Invalid amount. Minimum amount is 100 paise (₹1.00)." },
          { status: 400 }
        );
      }
      amountInRupees = body.is_rupees ? rawAmount : rawAmount / 100;
    }

    if (amountInRupees < 1) {
      return NextResponse.json(
        { error: "Invalid amount. Minimum amount is 100 paise (₹1.00)." },
        { status: 400 }
      );
    }

    const clientId = getCashfreeClientId();
    const clientSecret = getCashfreeClientSecret();

    // If explicit live request is requested and credentials are missing
    if (body.require_live && (!clientId || !clientSecret)) {
      return NextResponse.json(
        { error: "Cashfree credentials not configured. Please set CASHFREE_CLIENT_ID and CASHFREE_CLIENT_SECRET." },
        { status: 401 }
      );
    }

    const orderId = (
      body.order_id ||
      body.receipt ||
      `order_${Date.now()}_${Math.random().toString(36).substring(2, 7)}`
    ).replace(/[^a-zA-Z0-9_-]/g, "_").substring(0, 45);

    const customerPhone = body.customer_phone || body.phone || "9876543210";
    const customerName = body.customer_name || body.name || "Customer";
    const customerEmail = body.customer_email || body.email || "customer@example.com";

    try {
      const order = await createCashfreeOrder({
        orderId,
        orderAmount: amountInRupees,
        customerPhone,
        customerName,
        customerEmail,
        orderNote: body.notes?.toString() || body.order_note,
        returnUrl: body.return_url,
        notifyUrl: body.notify_url,
      });

      return NextResponse.json({
        order_id: order.orderId,
        payment_session_id: order.paymentSessionId,
        cf_order_id: order.cfOrderId,
        order_amount: order.orderAmount,
        // Also provide amount in paise for backward compatibility
        amount: Math.round(order.orderAmount * 100),
        currency: order.orderCurrency,
        is_mock: order.isMock,
      });
    } catch (apiError: any) {
      console.error("Cashfree order creation error:", apiError);
      const statusCode = apiError?.response?.status || apiError?.statusCode || apiError?.status || 500;
      const errorMsg =
        apiError?.response?.data?.message ||
        apiError?.message ||
        "Cashfree order creation failed";

      return NextResponse.json(
        { error: errorMsg },
        { status: statusCode >= 400 && statusCode < 600 ? statusCode : 500 }
      );
    }
  } catch (err: any) {
    console.error("Error creating Cashfree order:", err);
    return NextResponse.json(
      { error: err?.message || "Internal server error" },
      { status: 500 }
    );
  }
}
