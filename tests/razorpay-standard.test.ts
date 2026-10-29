import { describe, expect, it } from "vitest";
import crypto from "node:crypto";
import { POST as createOrderHandler } from "@/app/api/create-order/route";
import { POST as verifyPaymentHandler } from "@/app/api/verify-payment/route";

process.env.RAZORPAY_KEY_ID = process.env.RAZORPAY_KEY_ID || "rzp_test_TthV6CQXszsjl8";
process.env.RAZORPAY_KEY_SECRET = process.env.RAZORPAY_KEY_SECRET || "sbL3a76D0ImAsDiwCRNItHQE";
process.env.NEXT_PUBLIC_RAZORPAY_KEY_ID = process.env.NEXT_PUBLIC_RAZORPAY_KEY_ID || "rzp_test_TthV6CQXszsjl8";

const KEY_SECRET = process.env.RAZORPAY_KEY_SECRET;

describe("Razorpay Standard Checkout API Endpoints", () => {
  describe("POST /api/create-order", () => {
    it("validates that amount is at least 100 paise", async () => {
      const req = new Request("http://localhost:3000/api/create-order", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ amount: 99 }), // < 100 paise
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

    it("successfully creates a Razorpay order when amount >= 100 paise", async () => {
      const req = new Request("http://localhost:3000/api/create-order", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          amount: 50000,
          currency: "INR",
          receipt: "test_rcpt_101",
        }),
      });

      const res = await createOrderHandler(req);
      const data = await res.json();

      expect(res.status).toBe(200);
      expect(data.order_id).toBeDefined();
      expect(data.order_id).toMatch(/^order_/);
      expect(data.amount).toBe(50000);
      expect(data.currency).toBe("INR");
    });
  });

  describe("POST /api/verify-payment", () => {
    it("returns 400 if required fields are missing", async () => {
      const req = new Request("http://localhost:3000/api/verify-payment", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ order_id: "order_123" }), // Missing payment_id and signature
      });

      const res = await verifyPaymentHandler(req);
      const data = await res.json();

      expect(res.status).toBe(400);
      expect(data.error).toBeDefined();
    });

    it("returns 400 if the signature does not match", async () => {
      const orderId = "order_test_12345";
      const paymentId = "pay_test_67890";
      const invalidSignature = "invalid_tampered_signature_hex";

      const req = new Request("http://localhost:3000/api/verify-payment", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          order_id: orderId,
          payment_id: paymentId,
          razorpay_signature: invalidSignature,
        }),
      });

      const res = await verifyPaymentHandler(req);
      const data = await res.json();

      expect(res.status).toBe(400);
      expect(data.success).toBe(false);
      expect(data.error).toContain("Invalid payment signature");
    });

    it("returns 200 success if HMAC-SHA256 signature is valid", async () => {
      const orderId = "order_test_998877";
      const paymentId = "pay_test_112233";

      // Compute correct HMAC-SHA256(order_id + "|" + payment_id, KEY_SECRET)
      const payload = `${orderId}|${paymentId}`;
      const validSignature = crypto
        .createHmac("sha256", KEY_SECRET)
        .update(payload)
        .digest("hex");

      const req = new Request("http://localhost:3000/api/verify-payment", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          order_id: orderId,
          payment_id: paymentId,
          razorpay_signature: validSignature,
        }),
      });

      const res = await verifyPaymentHandler(req);
      const data = await res.json();

      expect(res.status).toBe(200);
      expect(data.success).toBe(true);
      expect(data.order_id).toBe(orderId);
      expect(data.payment_id).toBe(paymentId);
    });

    it("supports razorpay_order_id and razorpay_payment_id field names directly from checkout.js handler", async () => {
      const orderId = "order_sdk_445566";
      const paymentId = "pay_sdk_778899";

      const payload = `${orderId}|${paymentId}`;
      const validSignature = crypto
        .createHmac("sha256", KEY_SECRET)
        .update(payload)
        .digest("hex");

      const req = new Request("http://localhost:3000/api/verify-payment", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          razorpay_order_id: orderId,
          razorpay_payment_id: paymentId,
          razorpay_signature: validSignature,
        }),
      });

      const res = await verifyPaymentHandler(req);
      const data = await res.json();

      expect(res.status).toBe(200);
      expect(data.success).toBe(true);
    });
  });
});
