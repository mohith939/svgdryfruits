import { NextResponse } from "next/server";
import { getAdminSession } from "@/lib/auth";
import { getDb } from "@/db/client";
import { categories, comboItems, orderItems, products, reservations, variants } from "@/db/schema";

export async function POST() {
  const session = await getAdminSession();
  if (!session) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  try {
    const db = await getDb();
    await db.delete(reservations);
    await db.delete(orderItems);
    await db.delete(comboItems);
    await db.delete(variants);
    await db.delete(products);
    await db.delete(categories);

    return NextResponse.json({ ok: true, message: "Entire catalog successfully wiped." });
  } catch (err: any) {
    console.error("Wipe catalog error:", err);
    return NextResponse.json({ ok: false, error: err?.message || String(err) }, { status: 500 });
  }
}
