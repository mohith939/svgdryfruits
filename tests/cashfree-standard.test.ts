import { describe, expect, it } from "vitest";
import { POST as createOrderHandler } from "@/app/api/create-order/route";
import { POST as verifyPaymentHandler } from "@/app/api/verify-payment/route";
import { createCashfreeOrder, isCashfreeConfigured } from "@/lib/cashfree";

describe("Cashfree PG Checkout API Endpoints", () => {
  describe("POST /api/create-order", () => {
    it("validates that amount is at least 100 paise (₹1.00)", async () => {
      const req = new Request("http://localhost:3000/api/create-order", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ amount: 50 }), // < 100 paise
      });

      const res = await createOrderHandler(req);
      const data = await res.json();

      expect(res.status).toBe(400);
      expect(data.error).toContain("Minimum amount is 100 paise");
    });

    it("rejects invalid or missing amount with status 400", async () => {
      const req = new Request("http://localhost:3000/api/create-order", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ amount: "invalid" }),
      });

      const res = await createOrderHandler(req);
      expect(res.status).toBe(400);
    });

    it("successfully creates a Cashfree order returning payment_session_id", async () => {
      const req = new Request("http://localhost:3000/api/create-order", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          amount: 50000, // 50000 paise = ₹500.00
          currency: "INR",
          receipt: "test_cf_rcpt_101",
          customer_phone: "9876543210",
          customer_name: "Test Customer",
        }),
      });

      const res = await createOrderHandler(req);
      const data = await res.json();

      expect(res.status).toBe(200);
      expect(data.order_id).toBeDefined();
      expect(data.payment_session_id).toBeDefined();
      expect(data.order_amount).toBe(500);
      expect(data.currency).toBe("INR");
    });
  });

  describe("POST /api/verify-payment", () => {
    it("returns 400 if required order_id is missing", async () => {
      const req = new Request("http://localhost:3000/api/verify-payment", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({}),
      });

      const res = await verifyPaymentHandler(req);
      const data = await res.json();

      expect(res.status).toBe(400);
      expect(data.error).toBeDefined();
    });

    it("returns 200 success for simulated mock payment verification", async () => {
      const req = new Request("http://localhost:3000/api/verify-payment", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          order_id: "order_sim_998877",
          payment_id: "cf_pay_sim_112233",
          is_mock: true,
        }),
      });

      const res = await verifyPaymentHandler(req);
      const data = await res.json();

      expect(res.status).toBe(200);
      expect(data.success).toBe(true);
      expect(data.order_id).toBe("order_sim_998877");
    });
  });

  describe("Cashfree Core SDK", () => {
    it("generates an order with payment_session_id seamlessly in dev fallback", async () => {
      const order = await createCashfreeOrder({
        orderId: `test_cf_${Date.now()}`,
        orderAmount: 250,
        customerPhone: "9876543210",
      });

      expect(order.orderId).toBeDefined();
      expect(order.paymentSessionId).toBeDefined();
      expect(order.orderAmount).toBe(250);
      expect(order.orderCurrency).toBe("INR");
    });
  });
});
