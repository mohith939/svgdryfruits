import { NextResponse } from "next/server";
import { clearAdminSession } from "@/lib/auth";

export async function POST(req: Request) {
  await clearAdminSession();
  const accept = req.headers.get("accept") ?? "";
  if (accept.includes("application/json") && !accept.includes("text/html")) {
    return NextResponse.json({ ok: true });
  }
  return NextResponse.redirect(new URL("/admin/login", req.url), 303);
}

export async function GET(req: Request) {
  await clearAdminSession();
  return NextResponse.redirect(new URL("/admin/login", req.url), 303);
}
