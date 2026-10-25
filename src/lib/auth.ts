import crypto from "node:crypto";
import { cookies } from "next/headers";
import { and, eq, gt } from "drizzle-orm";
import { getDb } from "@/db/client";
import { admins, adminSessions } from "@/db/schema";

const COOKIE_NAME = "svg_admin_session";
const SESSION_TTL_MS = 7 * 24 * 60 * 60 * 1000; // 7 days

export function hashPassword(password: string): string {
  const salt = crypto.randomBytes(16).toString("hex");
  const hash = crypto.scryptSync(password, salt, 64).toString("hex");
  return `${salt}:${hash}`;
}

export function verifyPassword(password: string, combined: string): boolean {
  const [salt, storedHash] = combined.split(":");
  if (!salt || !storedHash) return false;
  const hash = crypto.scryptSync(password, salt, 64).toString("hex");
  const storedBuf = Buffer.from(storedHash, "utf8");
  const hashBuf = Buffer.from(hash, "utf8");
  if (storedBuf.length !== hashBuf.length) return false;
  return crypto.timingSafeEqual(storedBuf, hashBuf);
}

export async function bootstrapAdminFromEnv() {
  const email = (process.env.ADMIN_BOOTSTRAP_EMAIL || "svgdryfruits@gmail.com").trim().toLowerCase();
  const password = (process.env.ADMIN_BOOTSTRAP_PASSWORD || "Mohith@123").trim();

  const db = await getDb();
  const [existing] = await db.select().from(admins).where(eq(admins.email, email)).limit(1);
  if (!existing) {
    const passwordHash = hashPassword(password);
    await db.insert(admins).values({
      email,
      passwordHash,
    });
    console.log(`[AUTH] Bootstrapped admin: ${email}`);
  }
}

export async function createAdminSession(adminId: string) {
  const rawToken = crypto.randomBytes(32).toString("hex");
  const tokenHash = crypto.createHash("sha256").update(rawToken).digest("hex");
  const csrfToken = crypto.randomBytes(24).toString("hex");
  const expiresAt = new Date(Date.now() + SESSION_TTL_MS);

  const db = await getDb();
  await db.insert(adminSessions).values({
    adminId,
    tokenHash,
    csrfToken,
    expiresAt,
  });

  const cookieStore = await cookies();
  cookieStore.set(COOKIE_NAME, rawToken, {
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax",
    expires: expiresAt,
    path: "/",
  });

  return { csrfToken };
}

export async function getAdminSession() {
  const cookieStore = await cookies();
  const rawToken = cookieStore.get(COOKIE_NAME)?.value;
  if (!rawToken) return null;

  try {
    const tokenHash = crypto.createHash("sha256").update(rawToken).digest("hex");
    const db = await getDb();
    const [row] = await db
      .select({
        session: adminSessions,
        admin: admins,
      })
      .from(adminSessions)
      .innerJoin(admins, eq(adminSessions.adminId, admins.id))
      .where(eq(adminSessions.tokenHash, tokenHash))
      .limit(1);

    if (!row) return null;

    // Check expiration in JavaScript
    if (new Date(row.session.expiresAt).getTime() <= Date.now()) {
      return null;
    }

    return {
      admin: {
        id: row.admin.id,
        email: row.admin.email,
      },
      csrfToken: row.session.csrfToken,
    };
  } catch (err) {
    console.warn("getAdminSession lookup error:", err);
    return null;
  }
}

export async function clearAdminSession() {
  try {
    const cookieStore = await cookies();
    const rawToken = cookieStore.get(COOKIE_NAME)?.value;
    if (rawToken) {
      const tokenHash = crypto.createHash("sha256").update(rawToken).digest("hex");
      const db = await getDb();
      await db.delete(adminSessions).where(eq(adminSessions.tokenHash, tokenHash));
    }
    cookieStore.delete(COOKIE_NAME);
  } catch (err) {
    console.warn("clearAdminSession error:", err);
  }
}
