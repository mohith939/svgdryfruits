import { NextResponse } from "next/server";
import { eq } from "drizzle-orm";
import { z } from "zod";
import { getDb } from "@/db/client";
import { shippingSettings } from "@/db/schema";
import { getAdminSession } from "@/lib/auth";

const settingsSchema = z.object({
  flatChargePaise: z.number().int().min(0),
  freeThresholdPaise: z.number().int().min(0).optional().nullable(),
  active: z.boolean(),
});

export async function GET() {
  const session = await getAdminSession();
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const db = await getDb();
  const [s] = await db.select().from(shippingSettings).where(eq(shippingSettings.id, 1)).limit(1);

  return NextResponse.json({
    shipping: s || { flatChargePaise: 0, freeThresholdPaise: null, active: false },
    env: {
      siteUrl: process.env.NEXT_PUBLIC_SITE_URL || "http://localhost:3000",
      whatsappNumber: process.env.NEXT_PUBLIC_WHATSAPP_NUMBER || "",
      contactEmail: process.env.NEXT_PUBLIC_CONTACT_EMAIL || "",
      razorpayKeyConfigured: Boolean(process.env.RAZORPAY_KEY_ID && process.env.RAZORPAY_KEY_SECRET),
    },
  });
}

export async function PUT(req: Request) {
  const session = await getAdminSession();
  if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const raw = await req.json().catch(() => null);
  const parsed = settingsSchema.safeParse(raw);
  if (!parsed.success) {
    return NextResponse.json({ error: "Invalid shipping settings" }, { status: 400 });
  }

  const db = await getDb();
  await db
    .insert(shippingSettings)
    .values({ id: 1, ...parsed.data })
    .onConflictDoUpdate({
      target: shippingSettings.id,
      set: parsed.data,
    });

  return NextResponse.json({ ok: true });
}
