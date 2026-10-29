import type { PgDatabase, PgQueryResultHKT } from "drizzle-orm/pg-core";
import * as schema from "./schema";

export type Db = PgDatabase<PgQueryResultHKT, typeof schema>;

interface GlobalDbStore {
  cachedDb?: Db;
  cachedDbPromise?: Promise<Db>;
  cachedClient?: unknown;
  migrated?: boolean;
}

const globalForDb = globalThis as unknown as GlobalDbStore;

// Uses DATABASE_URL (any PostgreSQL) when set; otherwise an embedded local PGlite database.
export async function getDb(): Promise<Db> {
  if (globalForDb.cachedDb) return globalForDb.cachedDb;
  if (globalForDb.cachedDbPromise) return globalForDb.cachedDbPromise;

  globalForDb.cachedDbPromise = (async () => {
    const url = process.env.DATABASE_URL;
    if (url) {
      const { Pool } = await import("pg");
      const { drizzle } = await import("drizzle-orm/node-postgres");
      const pool = new Pool({ connectionString: url });
      const db = drizzle(pool, { schema }) as unknown as Db;

      // Auto-ensure schema on remote Postgres / Neon if tables do not exist
      if (!globalForDb.migrated) {
        await initPostgresSchema(pool, db);
        globalForDb.migrated = true;
      }

      globalForDb.cachedDb = db;
      return db;
    } else {
      const db = await createLocalDb(process.env.PGLITE_DIR ?? ".data/pglite");
      globalForDb.cachedDb = db;
      return db;
    }
  })().catch((err) => {
    globalForDb.cachedDbPromise = undefined;
    throw err;
  });

  return globalForDb.cachedDbPromise;
}

async function initPostgresSchema(pool: any, db: Db) {
  try {
    const check = await pool.query("SELECT to_regclass('public.products') as tbl;");
    if (!check.rows?.[0]?.tbl) {
      console.log("[DB] Products table not found on remote Postgres. Initializing tables & seeding...");
      await pool.query(CORE_DDL);
      try {
        const { REAL_CATEGORIES } = await import("@/lib/catalog-data");
        for (const c of REAL_CATEGORIES) {
          await db
            .insert(schema.categories)
            .values({
              id: c.id,
              slug: c.slug,
              name: c.name,
              description: c.description ?? null,
              imageUrl: c.imageUrl ?? null,
              sortOrder: c.sortOrder ?? 0,
              active: true,
            })
            .onConflictDoNothing();
        }
        console.log("[DB] Categories auto-seeded successfully.");
      } catch (catErr) {
        console.warn("[DB] Auto-seed categories notice:", catErr);
      }
      try {
        const { bootstrapAdminFromEnv } = await import("@/lib/auth");
        await bootstrapAdminFromEnv();
        console.log("[DB] Admin bootstrapped successfully.");
      } catch (authErr) {
        console.warn("[DB] Auto-bootstrap admin notice:", authErr);
      }
    }
  } catch (err) {
    console.warn("[DB] Postgres init schema check notice:", err);
  }

  try {
    await pool.query('ALTER TABLE "categories" ADD COLUMN IF NOT EXISTS "image_url" text;');
    await pool.query('ALTER TABLE "products" ADD COLUMN IF NOT EXISTS "images" jsonb DEFAULT \'[]\'::jsonb;');
    await pool.query('ALTER TABLE "coupons" ADD COLUMN IF NOT EXISTS "name" text;');
    await pool.query('ALTER TABLE "coupons" ADD COLUMN IF NOT EXISTS "description" text;');
    await pool.query('ALTER TABLE "order_items" ALTER COLUMN "variant_id" DROP NOT NULL;');
  } catch {}
}

const CORE_DDL = `
  CREATE TABLE IF NOT EXISTS "admins" (
    "id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
    "email" text NOT NULL UNIQUE,
    "password_hash" text NOT NULL,
    "created_at" timestamp with time zone DEFAULT now() NOT NULL
  );
  CREATE TABLE IF NOT EXISTS "admin_sessions" (
    "id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
    "admin_id" uuid NOT NULL REFERENCES admins(id) ON DELETE CASCADE,
    "token_hash" text NOT NULL UNIQUE,
    "csrf_token" text NOT NULL,
    "expires_at" timestamp with time zone NOT NULL
  );
  CREATE TABLE IF NOT EXISTS "categories" (
    "id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
    "slug" text NOT NULL UNIQUE,
    "name" text NOT NULL,
    "description" text,
    "image_url" text,
    "sort_order" integer DEFAULT 0 NOT NULL,
    "active" boolean DEFAULT true NOT NULL,
    "created_at" timestamp with time zone DEFAULT now() NOT NULL
  );
  CREATE TABLE IF NOT EXISTS "products" (
    "id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
    "category_id" uuid REFERENCES categories(id),
    "slug" text NOT NULL UNIQUE,
    "name" text NOT NULL,
    "description" text,
    "image_url" text,
    "images" jsonb DEFAULT '[]'::jsonb,
    "is_combo" boolean DEFAULT false NOT NULL,
    "active" boolean DEFAULT true NOT NULL,
    "is_demo" boolean DEFAULT false NOT NULL,
    "created_at" timestamp with time zone DEFAULT now() NOT NULL
  );
  CREATE TABLE IF NOT EXISTS "variants" (
    "id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
    "product_id" uuid NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    "sku" text NOT NULL UNIQUE,
    "label" text NOT NULL,
    "price_paise" integer NOT NULL,
    "compare_price_paise" integer,
    "stock" integer DEFAULT 0 NOT NULL,
    "low_stock_threshold" integer DEFAULT 5 NOT NULL,
    "active" boolean DEFAULT true NOT NULL
  );
  CREATE TABLE IF NOT EXISTS "orders" (
    "id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
    "order_number" text NOT NULL UNIQUE,
    "tracking_token" text NOT NULL UNIQUE,
    "payment_status" text DEFAULT 'pending' NOT NULL,
    "fulfillment_status" text DEFAULT 'awaiting_payment' NOT NULL,
    "customer_name" text NOT NULL,
    "customer_email" text,
    "customer_phone" text NOT NULL,
    "shipping_address" jsonb NOT NULL,
    "subtotal_paise" integer NOT NULL,
    "discount_paise" integer DEFAULT 0 NOT NULL,
    "shipping_paise" integer DEFAULT 0 NOT NULL,
    "total_paise" integer NOT NULL,
    "coupon_code" text,
    "razorpay_order_id" text,
    "courier" text,
    "tracking_number" text,
    "reservation_expires_at" timestamp with time zone,
    "created_at" timestamp with time zone DEFAULT now() NOT NULL
  );
  CREATE TABLE IF NOT EXISTS "order_items" (
    "id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
    "order_id" uuid NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    "variant_id" uuid REFERENCES variants(id),
    "product_name" text NOT NULL,
    "variant_label" text NOT NULL,
    "unit_price_paise" integer NOT NULL,
    "quantity" integer NOT NULL
  );
  CREATE TABLE IF NOT EXISTS "coupons" (
    "id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
    "code" text NOT NULL UNIQUE,
    "name" text,
    "description" text,
    "type" text NOT NULL,
    "value" integer NOT NULL,
    "min_subtotal_paise" integer DEFAULT 0 NOT NULL,
    "max_discount_paise" integer,
    "active" boolean DEFAULT true NOT NULL,
    "starts_at" timestamp with time zone,
    "expires_at" timestamp with time zone,
    "usage_limit" integer,
    "times_used" integer DEFAULT 0 NOT NULL
  );
  CREATE TABLE IF NOT EXISTS "combo_items" (
    "id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
    "combo_variant_id" uuid NOT NULL REFERENCES variants(id) ON DELETE CASCADE,
    "component_variant_id" uuid NOT NULL REFERENCES variants(id),
    "quantity" integer DEFAULT 1 NOT NULL
  );
  CREATE TABLE IF NOT EXISTS "inventory_movements" (
    "id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
    "variant_id" uuid NOT NULL REFERENCES variants(id),
    "delta" integer NOT NULL,
    "reason" text NOT NULL,
    "order_id" uuid,
    "created_at" timestamp with time zone DEFAULT now() NOT NULL
  );
  CREATE TABLE IF NOT EXISTS "reservations" (
    "id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
    "order_id" uuid NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    "variant_id" uuid NOT NULL REFERENCES variants(id),
    "quantity" integer NOT NULL,
    "status" text DEFAULT 'held' NOT NULL,
    "expires_at" timestamp with time zone NOT NULL
  );
  CREATE TABLE IF NOT EXISTS "payments" (
    "id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
    "order_id" uuid NOT NULL REFERENCES orders(id),
    "razorpay_payment_id" text UNIQUE,
    "amount_paise" integer NOT NULL,
    "status" text NOT NULL,
    "created_at" timestamp with time zone DEFAULT now() NOT NULL
  );
  CREATE TABLE IF NOT EXISTS "webhook_events" (
    "id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
    "event_id" text NOT NULL UNIQUE,
    "type" text NOT NULL,
    "processed_at" timestamp with time zone DEFAULT now() NOT NULL
  );
  CREATE TABLE IF NOT EXISTS "refunds" (
    "id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
    "order_id" uuid NOT NULL REFERENCES orders(id),
    "amount_paise" integer NOT NULL,
    "reason" text,
    "razorpay_refund_id" text UNIQUE,
    "created_at" timestamp with time zone DEFAULT now() NOT NULL
  );
  CREATE TABLE IF NOT EXISTS "shipping_settings" (
    "id" integer PRIMARY KEY DEFAULT 1,
    "flat_charge_paise" integer DEFAULT 0 NOT NULL,
    "free_threshold_paise" integer,
    "active" boolean DEFAULT false NOT NULL
  );
  CREATE TABLE IF NOT EXISTS "audit_events" (
    "id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
    "actor" text NOT NULL,
    "action" text NOT NULL,
    "detail" jsonb,
    "created_at" timestamp with time zone DEFAULT now() NOT NULL
  );
`;

export async function createLocalDb(dataDir?: string): Promise<Db> {
  const { PGlite } = await import("@electric-sql/pglite");
  const { drizzle } = await import("drizzle-orm/pglite");
  const { migrate } = await import("drizzle-orm/pglite/migrator");

  let client: any;

  // In serverless environments (e.g. Vercel, AWS Lambda), write to OS temp directory
  let targetDir = dataDir;
  if (dataDir && (process.env.VERCEL || process.env.AWS_LAMBDA_FUNCTION_NAME)) {
    try {
      const os = await import("node:os");
      const path = await import("node:path");
      targetDir = path.join(os.tmpdir(), "pglite");
    } catch {
      targetDir = undefined;
    }
  }

  if (targetDir) {
    if (globalForDb.cachedClient) {
      client = globalForDb.cachedClient;
    } else {
      const fs = await import("node:fs");
      const path = await import("node:path");
      let diskReady = false;

      try {
        fs.mkdirSync(targetDir, { recursive: true });
        const lockFile = path.join(targetDir, "postmaster.pid");
        if (fs.existsSync(lockFile)) {
          try {
            fs.unlinkSync(lockFile);
          } catch {}
        }
        client = new PGlite(targetDir);
        await client.waitReady;
        diskReady = true;
      } catch (dirErr) {
        console.warn("Notice: Persistent database directory unavailable, falling back to in-memory:", dirErr);
      }

      if (!diskReady) {
        client = new PGlite();
        await client.waitReady;
      }
      globalForDb.cachedClient = client;
    }
  } else {
    client = new PGlite();
    await client.waitReady;
  }

  const db = drizzle(client, { schema }) as unknown as Db;

  // Always verify if core tables exist; if not, initialize them reliably
  let tablesExist = false;
  try {
    const check = await client.query("SELECT to_regclass('public.admins') as tbl;");
    if (check.rows?.[0]?.tbl) {
      tablesExist = true;
    }
  } catch {}

  if (!tablesExist) {
    let migrationSucceeded = false;
    try {
      await migrate(db as any, { migrationsFolder: "drizzle" });
      migrationSucceeded = true;
    } catch (migErr) {
      console.warn("Drizzle folder migration notice:", migErr);
    }

    if (!migrationSucceeded) {
      try {
        const executeDdl = client.exec ? (sql: string) => client.exec(sql) : (sql: string) => client.query(sql);
        await executeDdl(CORE_DDL);
      } catch (ddlErr) {
        console.warn("DDL execution fallback notice:", ddlErr);
      }
    }
  }

  // Always ensure schema evolution columns exist
  try {
    await client.query('ALTER TABLE "categories" ADD COLUMN IF NOT EXISTS "image_url" text;');
    await client.query('ALTER TABLE "products" ADD COLUMN IF NOT EXISTS "images" jsonb DEFAULT \'[]\'::jsonb;');
    await client.query('ALTER TABLE "coupons" ADD COLUMN IF NOT EXISTS "name" text;');
    await client.query('ALTER TABLE "coupons" ADD COLUMN IF NOT EXISTS "description" text;');
    await client.query('ALTER TABLE "order_items" ALTER COLUMN "variant_id" DROP NOT NULL;');
  } catch {}

  return db;
}


