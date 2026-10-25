import { sql } from "drizzle-orm";
import {
  boolean, check, index, integer, jsonb, pgTable, text, timestamp, uniqueIndex, uuid,
} from "drizzle-orm/pg-core";

const id = () => uuid("id").primaryKey().defaultRandom();
const createdAt = () => timestamp("created_at", { withTimezone: true }).notNull().defaultNow();

export const categories = pgTable("categories", {
  id: id(),
  slug: text("slug").notNull().unique(),
  name: text("name").notNull(),
  description: text("description"),
  imageUrl: text("image_url"),
  sortOrder: integer("sort_order").notNull().default(0),
  active: boolean("active").notNull().default(true),
  createdAt: createdAt(),
});

export const products = pgTable("products", {
  id: id(),
  categoryId: uuid("category_id").references(() => categories.id),
  slug: text("slug").notNull().unique(),
  name: text("name").notNull(),
  description: text("description"),
  imageUrl: text("image_url"),
  images: jsonb("images").$type<string[]>().default([]),
  isCombo: boolean("is_combo").notNull().default(false),
  active: boolean("active").notNull().default(true),
  isDemo: boolean("is_demo").notNull().default(false),
  createdAt: createdAt(),
});

export const variants = pgTable("variants", {
  id: id(),
  productId: uuid("product_id").notNull().references(() => products.id, { onDelete: "cascade" }),
  sku: text("sku").notNull().unique(),
  label: text("label").notNull(),
  pricePaise: integer("price_paise").notNull(),
  comparePricePaise: integer("compare_price_paise"),
  stock: integer("stock").notNull().default(0),
  lowStockThreshold: integer("low_stock_threshold").notNull().default(5),
  active: boolean("active").notNull().default(true),
}, (t) => [
  check("variants_price_nonneg", sql`${t.pricePaise} >= 0`),
  check("variants_stock_nonneg", sql`${t.stock} >= 0`),
]);

// Combo bundles reference component variants whose stock is consumed.
export const comboItems = pgTable("combo_items", {
  id: id(),
  comboVariantId: uuid("combo_variant_id").notNull().references(() => variants.id, { onDelete: "cascade" }),
  componentVariantId: uuid("component_variant_id").notNull().references(() => variants.id),
  quantity: integer("quantity").notNull().default(1),
});

export const inventoryMovements = pgTable("inventory_movements", {
  id: id(),
  variantId: uuid("variant_id").notNull().references(() => variants.id),
  delta: integer("delta").notNull(),
  reason: text("reason").notNull(),
  orderId: uuid("order_id"),
  createdAt: createdAt(),
});

export const coupons = pgTable("coupons", {
  id: id(),
  code: text("code").notNull().unique(),
  name: text("name"),
  description: text("description"),
  type: text("type").notNull(),
  value: integer("value").notNull(),
  minSubtotalPaise: integer("min_subtotal_paise").notNull().default(0),
  maxDiscountPaise: integer("max_discount_paise"),
  active: boolean("active").notNull().default(true),
  startsAt: timestamp("starts_at", { withTimezone: true }),
  expiresAt: timestamp("expires_at", { withTimezone: true }),
  usageLimit: integer("usage_limit"),
  timesUsed: integer("times_used").notNull().default(0),
});

export const orders = pgTable("orders", {
  id: id(),
  orderNumber: text("order_number").notNull().unique(),
  trackingToken: text("tracking_token").notNull().unique(),
  paymentStatus: text("payment_status").notNull().default("pending"),
  fulfillmentStatus: text("fulfillment_status").notNull().default("awaiting_payment"),
  customerName: text("customer_name").notNull(),
  customerEmail: text("customer_email"),
  customerPhone: text("customer_phone").notNull(),
  shippingAddress: jsonb("shipping_address").notNull(),
  subtotalPaise: integer("subtotal_paise").notNull(),
  discountPaise: integer("discount_paise").notNull().default(0),
  shippingPaise: integer("shipping_paise").notNull().default(0),
  totalPaise: integer("total_paise").notNull(),
  couponCode: text("coupon_code"),
  razorpayOrderId: text("razorpay_order_id").unique(),
  courier: text("courier"),
  trackingNumber: text("tracking_number"),
  reservationExpiresAt: timestamp("reservation_expires_at", { withTimezone: true }),
  createdAt: createdAt(),
}, (t) => [index("orders_status_idx").on(t.paymentStatus, t.fulfillmentStatus)]);

export const orderItems = pgTable("order_items", {
  id: id(),
  orderId: uuid("order_id").notNull().references(() => orders.id, { onDelete: "cascade" }),
  variantId: uuid("variant_id").notNull().references(() => variants.id),
  // Snapshot so later catalog edits never change past orders.
  productName: text("product_name").notNull(),
  variantLabel: text("variant_label").notNull(),
  unitPricePaise: integer("unit_price_paise").notNull(),
  quantity: integer("quantity").notNull(),
});

export const reservations = pgTable("reservations", {
  id: id(),
  orderId: uuid("order_id").notNull().references(() => orders.id, { onDelete: "cascade" }),
  variantId: uuid("variant_id").notNull().references(() => variants.id),
  quantity: integer("quantity").notNull(),
  status: text("status").notNull().default("held"), // held | committed | released
  expiresAt: timestamp("expires_at", { withTimezone: true }).notNull(),
});

export const payments = pgTable("payments", {
  id: id(),
  orderId: uuid("order_id").notNull().references(() => orders.id),
  razorpayPaymentId: text("razorpay_payment_id").unique(),
  amountPaise: integer("amount_paise").notNull(),
  status: text("status").notNull(),
  createdAt: createdAt(),
});

export const webhookEvents = pgTable("webhook_events", {
  id: id(),
  eventId: text("event_id").notNull(),
  type: text("type").notNull(),
  processedAt: timestamp("processed_at", { withTimezone: true }).notNull().defaultNow(),
}, (t) => [uniqueIndex("webhook_event_unique").on(t.eventId)]);

export const refunds = pgTable("refunds", {
  id: id(),
  orderId: uuid("order_id").notNull().references(() => orders.id),
  amountPaise: integer("amount_paise").notNull(),
  note: text("note"),
  createdAt: createdAt(),
});

export const admins = pgTable("admins", {
  id: id(),
  email: text("email").notNull().unique(),
  passwordHash: text("password_hash").notNull(),
  createdAt: createdAt(),
});

export const adminSessions = pgTable("admin_sessions", {
  id: id(),
  adminId: uuid("admin_id").notNull().references(() => admins.id, { onDelete: "cascade" }),
  tokenHash: text("token_hash").notNull().unique(),
  csrfToken: text("csrf_token").notNull(),
  expiresAt: timestamp("expires_at", { withTimezone: true }).notNull(),
});

export const shippingSettings = pgTable("shipping_settings", {
  id: integer("id").primaryKey().default(1),
  flatChargePaise: integer("flat_charge_paise").notNull().default(0),
  freeThresholdPaise: integer("free_threshold_paise"),
  active: boolean("active").notNull().default(false),
});

export const auditEvents = pgTable("audit_events", {
  id: id(),
  actor: text("actor").notNull(),
  action: text("action").notNull(),
  detail: jsonb("detail"),
  createdAt: createdAt(),
});
