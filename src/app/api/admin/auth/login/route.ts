import { NextResponse } from "next/server";
import { eq } from "drizzle-orm";
import { z } from "zod";
import { getDb } from "@/db/client";
import { admins } from "@/db/schema";
import { bootstrapAdminFromEnv, createAdminSession, verifyPassword } from "@/lib/auth";

const loginSchema = z.object({
  email: z.string().trim().email(),
  password: z.string().min(1),
});

export async function POST(req: Request) {
  try {
    const raw = await req.json().catch(() => null);
    const parsed = loginSchema.safeParse(raw);

    if (!parsed.success) {
      return NextResponse.json({ error: "Please enter a valid email and password" }, { status: 400 });
    }

    const { email, password } = parsed.data;

    // Bootstrap initial admin if environment variables are provided and no admin exists
    try {
      await bootstrapAdminFromEnv();
    } catch (bootErr) {
      console.warn("Bootstrap admin notice:", bootErr);
    }

    const db = await getDb();
    const [admin] = await db
      .select()
      .from(admins)
      .where(eq(admins.email, email.toLowerCase()))
      .limit(1);

    if (!admin) {
      // Timing attack mitigation: do dummy hash comparison
      verifyPassword(password, "0000000000000000:0000000000000000");
      return NextResponse.json({ error: "Invalid email or password" }, { status: 401 });
    }

    const isValid = verifyPassword(password, admin.passwordHash);
    if (!isValid) {
      return NextResponse.json({ error: "Invalid email or password" }, { status: 401 });
    }

    const { csrfToken } = await createAdminSession(admin.id, admin.email);

    return NextResponse.json({
      ok: true,
      csrfToken,
      admin: {
        id: admin.id,
        email: admin.email,
      },
    });
  } catch (err: any) {
    console.error("Admin login error:", err);
    return NextResponse.json({ error: err?.message || "Authentication system error" }, { status: 500 });
  }
}

