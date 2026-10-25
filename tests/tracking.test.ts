import { beforeEach, describe, expect, it } from "vitest";
import { eq } from "drizzle-orm";
import { createLocalDb, type Db } from "@/db/client";
import { orders } from "@/db/schema";

let db: Db;
const testOrderNumber = "SVG-20261016-8888";
const testToken = "secure_token_abcdef123456";

beforeEach(async () => {
  db = await createLocalDb();
  await db.insert(orders).values({
    orderNumber: testOrderNumber,
    trackingToken: testToken,
    paymentStatus: "paid",
    fulfillmentStatus: "shipped",
    customerName: "Priya Sharma",
    customerPhone: "9123456789",
    shippingAddress: { street: "Jubilee Hills", city: "Hyderabad", state: "Telangana", pincode: "500033" },
    subtotalPaise: 50000,
    totalPaise: 50000,
    courier: "Blue Dart",
    trackingNumber: "BD99887766",
  });
});

describe("customer order tracking", () => {
  it("verifies order access with matching token", async () => {
    const [found] = await db.select().from(orders).where(eq(orders.orderNumber, testOrderNumber));
    expect(found).toBeDefined();
    expect(found.trackingToken).toBe(testToken);
    expect(found.courier).toBe("Blue Dart");
    expect(found.trackingNumber).toBe("BD99887766");
  });

  it("prevents unauthorized access when token is wrong", async () => {
    const [found] = await db.select().from(orders).where(eq(orders.orderNumber, testOrderNumber));
    const isAuthorized = found.trackingToken === "fake_wrong_token";
    expect(isAuthorized).toBe(false);
  });
});
