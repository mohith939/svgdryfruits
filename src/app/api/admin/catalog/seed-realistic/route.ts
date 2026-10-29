import { NextResponse } from "next/server";
import { getAdminSession } from "@/lib/auth";
import { seedRealData } from "@/lib/seed-catalog";

export async function POST() {
  const session = await getAdminSession();
  if (!session) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  try {
    await seedRealData();
    return NextResponse.json({ ok: true, message: "Realistic dry fruits catalog successfully seeded." });
  } catch (err: any) {
    console.error("Seed realistic catalog error:", err);
    return NextResponse.json({ ok: false, error: err?.message || String(err) }, { status: 500 });
  }
}
