import { NextResponse } from "next/server";
import { writeFile } from "node:fs/promises";
import path from "node:path";
import crypto from "node:crypto";
import { getAdminSession } from "@/lib/auth";

const ALLOWED_TYPES = new Set([
  "image/jpeg",
  "image/png",
  "image/webp",
  "image/gif",
  "image/svg+xml",
]);

const MAX_SIZE_BYTES = 8 * 1024 * 1024; // 8MB

export async function POST(req: Request) {
  try {
    const session = await getAdminSession();
    if (!session) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const formData = await req.formData();
    const file = formData.get("file");

    if (!file || typeof file === "string") {
      return NextResponse.json({ error: "No file provided" }, { status: 400 });
    }

    const blob = file as Blob;

    if (!ALLOWED_TYPES.has(blob.type)) {
      return NextResponse.json(
        { error: "Invalid file format. Only JPEG, PNG, WebP, GIF, and SVG images are accepted." },
        { status: 400 }
      );
    }

    if (blob.size > MAX_SIZE_BYTES) {
      return NextResponse.json(
        { error: "File size exceeds limit of 8MB." },
        { status: 400 }
      );
    }

    const bytes = Buffer.from(await blob.arrayBuffer());

    // Generate safe clean filename
    const originalName = (file as any).name || "upload.jpg";
    const ext = path.extname(originalName).toLowerCase() || (blob.type === "image/png" ? ".png" : ".jpg");
    const base = path
      .basename(originalName, ext)
      .toLowerCase()
      .replace(/[^a-z0-9_-]/g, "-")
      .substring(0, 32);

    const uniqueTag = crypto.randomBytes(4).toString("hex");
    const safeFilename = `${base}-${uniqueTag}${ext}`;
    const uploadDir = path.join(process.cwd(), "public", "uploads");
    const filePath = path.join(uploadDir, safeFilename);

    await writeFile(filePath, bytes);

    const publicUrl = `/uploads/${safeFilename}`;
    return NextResponse.json({ ok: true, url: publicUrl });
  } catch (err: any) {
    console.error("Upload error:", err);
    return NextResponse.json(
      { error: err?.message || "Failed to upload file." },
      { status: 500 }
    );
  }
}
