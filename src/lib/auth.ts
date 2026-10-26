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
  } else if (!verifyPassword(password, existing.passwordHash)) {
    const passwordHash = hashPassword(password);
    await db.update(admins).set({ passwordHash }).where(eq(admins.id, existing.id));
    console.log(`[AUTH] Updated admin password for: ${email}`);
  }
}


const AUTH_SECRET = process.env.ADMIN_SESSION_SECRET || "svg-dry-fruits-secure-admin-session-secret-key-2026";

function createSignedToken(payload: { adminId: string; email: string; csrfToken: string; expiresAt: number }): string {
  const json = JSON.stringify(payload);
  const sig = crypto.createHmac("sha256", AUTH_SECRET).update(json).digest("hex");
  return Buffer.from(`${json}###${sig}`).toString("base64url");
}

function verifySignedToken(token: string): { adminId: string; email: string; csrfToken: string; expiresAt: number } | null {
  try {
    const raw = Buffer.from(token, "base64url").toString("utf8");
    const [json, sig] = raw.split("###");
    if (!json || !sig) return null;
    const expectedSig = crypto.createHmac("sha256", AUTH_SECRET).update(json).digest("hex");
    if (sig !== expectedSig) return null;
    const data = JSON.parse(json);
    if (data.expiresAt <= Date.now()) return null;
    return data;
  } catch {
    return null;
  }
}

export async function createAdminSession(adminId: string, adminEmail?: string) {
  const email = adminEmail || "svgdryfruits@gmail.com";
  const csrfToken = crypto.randomBytes(24).toString("hex");
  const expiresAt = new Date(Date.now() + SESSION_TTL_MS);

  const signedToken = createSignedToken({
    adminId,
    email,
    csrfToken,
    expiresAt: expiresAt.getTime(),
  });

  // Record in database if DB is online
  try {
    const tokenHash = crypto.createHash("sha256").update(signedToken).digest("hex");
    const db = await getDb();
    await db.insert(adminSessions).values({
      adminId,
      tokenHash,
      csrfToken,
      expiresAt,
    }).catch(() => {});
  } catch {}

  const cookieStore = await cookies();
  cookieStore.set(COOKIE_NAME, signedToken, {
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
  const token = cookieStore.get(COOKIE_NAME)?.value;
  if (!token) return null;

  // 1. Verify cryptographic HMAC signature (instant, 0ms, zero-database dependency across all serverless containers)
  const verified = verifySignedToken(token);
  if (verified) {
    return {
      admin: {
        id: verified.adminId,
        email: verified.email,
      },
      csrfToken: verified.csrfToken,
    };
  }

  // 2. Fallback to legacy database session lookup if older raw token is provided
  try {
    const tokenHash = crypto.createHash("sha256").update(token).digest("hex");
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
    const token = cookieStore.get(COOKIE_NAME)?.value;
    if (token) {
      try {
        const tokenHash = crypto.createHash("sha256").update(token).digest("hex");
        const db = await getDb();
        await db.delete(adminSessions).where(eq(adminSessions.tokenHash, tokenHash)).catch(() => {});
      } catch {}
    }
    cookieStore.delete(COOKIE_NAME);
  } catch (err) {
    console.warn("clearAdminSession error:", err);
  }
}

