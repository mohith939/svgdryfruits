import { NextResponse } from "next/server";
import { z } from "zod";
import { getDb } from "@/db/client";
import { auditEvents } from "@/db/schema";

const contactSchema = z.object({
  name: z.string().trim().min(2, "Name must be at least 2 characters").max(100),
  email: z.string().trim().email("Please enter a valid email address"),
  phone: z.string().trim().max(15).optional(),
  subject: z.string().trim().min(2, "Subject is required").max(100),
  message: z.string().trim().min(10, "Message must be at least 10 characters").max(2000),
});

export async function POST(req: Request) {
  try {
    const raw = await req.json().catch(() => null);
    const parsed = contactSchema.safeParse(raw);

    if (!parsed.success) {
      return NextResponse.json({
        error: parsed.error.issues[0]?.message || "Invalid contact form details",
      }, { status: 400 });
    }

    const { name, email, phone, subject, message } = parsed.data;
    const db = await getDb();

    // Persist contact enquiry in audit log so admin can review
    await db.insert(auditEvents).values({
      actor: email,
      action: "contact_inquiry",
      detail: {
        name,
        email,
        phone: phone || null,
        subject,
        message,
        receivedAt: new Date().toISOString(),
      },
    });

    return NextResponse.json({
      ok: true,
      message: "Thank you for reaching out! We have received your message and will get back to you shortly.",
    });
  } catch (err: unknown) {
    console.error("Contact API error:", err);
    return NextResponse.json({ error: "Failed to send message. Please try again." }, { status: 500 });
  }
}
