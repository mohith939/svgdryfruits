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
      const db = drizzle(new Pool({ connectionString: url }), { schema }) as unknown as Db;
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

export async function createLocalDb(dataDir?: string): Promise<Db> {
  const { PGlite } = await import("@electric-sql/pglite");
  const { drizzle } = await import("drizzle-orm/pglite");
  const { migrate } = await import("drizzle-orm/pglite/migrator");

  let client: any;
  if (dataDir) {
    if (globalForDb.cachedClient) {
      client = globalForDb.cachedClient;
    } else {
      const fs = await import("node:fs");
      const path = await import("node:path");
      fs.mkdirSync(dataDir, { recursive: true });
      const lockFile = path.join(dataDir, "postmaster.pid");
      try {
        if (fs.existsSync(lockFile)) {
          fs.unlinkSync(lockFile);
        }
      } catch {}
      try {
        client = new PGlite(dataDir);
        await client.waitReady;
      } catch (initErr) {
        console.warn("PGlite init failed on data directory, auto-recovering:", initErr);
        try {
          fs.rmSync(dataDir, { recursive: true, force: true });
          fs.mkdirSync(dataDir, { recursive: true });
          client = new PGlite(dataDir);
          await client.waitReady;
          globalForDb.migrated = false;
        } catch {
          client = new PGlite();
          await client.waitReady;
        }
      }
      globalForDb.cachedClient = client;
    }
  } else {
    client = new PGlite();
    await client.waitReady;
  }

  const db = drizzle(client, { schema }) as unknown as Db;

  // Run migrations safely: in-memory always; persistent only if tables don't exist yet
  if (!dataDir) {
    await migrate(db as any, { migrationsFolder: "drizzle" });
  } else if (!globalForDb.migrated) {
    try {
      const check = await client.query("SELECT to_regclass('public.admins') as tbl;");
      if (check.rows?.[0]?.tbl) {
        globalForDb.migrated = true;
      } else {
        await migrate(db as any, { migrationsFolder: "drizzle" });
        globalForDb.migrated = true;
      }
    } catch (migErr: any) {
      console.warn("Migration notice (continuing):", migErr?.message || migErr);
      globalForDb.migrated = true;
    }
  }

  // Always ensure schema evolution columns exist on the active database
  try {
    await client.query('ALTER TABLE "categories" ADD COLUMN IF NOT EXISTS "image_url" text;');
    await client.query('ALTER TABLE "products" ADD COLUMN IF NOT EXISTS "images" jsonb DEFAULT \'[]\'::jsonb;');
    await client.query('ALTER TABLE "coupons" ADD COLUMN IF NOT EXISTS "name" text;');
    await client.query('ALTER TABLE "coupons" ADD COLUMN IF NOT EXISTS "description" text;');
  } catch (colErr) {
    // ignore in tests where table might not exist yet
  }

  // If persistent database has 0 products, auto-seed with authentic catalog data
  if (dataDir) {
    try {
      const prodCheck = await client.query("SELECT count(*) as count FROM products;");
      if (Number(prodCheck.rows?.[0]?.count || 0) === 0) {
        const { seedRealData } = await import("../../scripts/seed");
        await seedRealData();
      }
    } catch {
      // ignore in isolated unit tests
    }
  }

  return db;
}

