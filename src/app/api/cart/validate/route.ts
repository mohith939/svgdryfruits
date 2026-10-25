import { NextResponse } from "next/server";
import { z } from "zod";
import { priceCart } from "@/lib/cart-server";

const body = z.object({
  lines: z.array(z.object({ variantId: z.string().min(1), quantity: z.number().int().min(1).max(20) })).max(50),
  couponCode: z.string().max(40).optional(),
});

export async function POST(req: Request) {
  const parsed = body.safeParse(await req.json().catch(() => null));
  if (!parsed.success) return NextResponse.json({ error: "Invalid cart" }, { status: 400 });
  return NextResponse.json(await priceCart(parsed.data.lines, parsed.data.couponCode));
}
