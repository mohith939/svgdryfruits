import { NextResponse } from "next/server";
import { writeFile, mkdir } from "node:fs/promises";
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

const MAX_SIZE_BYTES = 10 * 1024 * 1024; // 10MB

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
        { error: "File size exceeds limit of 10MB." },
        { status: 400 }
      );
    }

    const bytes = Buffer.from(await blob.arrayBuffer());
    const mimeType = blob.type || "image/jpeg";
    const dataUrl = `data:${mimeType};base64,${bytes.toString("base64")}`;

    // On serverless environments (e.g. Vercel, AWS Lambda), the filesystem is read-only (/var/task).
    // In those environments, immediately return the self-contained Data URL.
    if (process.env.VERCEL || process.env.AWS_LAMBDA_FUNCTION_NAME) {
      return NextResponse.json({ ok: true, url: dataUrl });
    }

    // On local environment, attempt to write to public/uploads
    try {
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

      await mkdir(uploadDir, { recursive: true }).catch(() => {});
      const filePath = path.join(uploadDir, safeFilename);

      await writeFile(filePath, bytes);
      return NextResponse.json({ ok: true, url: `/uploads/${safeFilename}` });
    } catch (fsErr: any) {
      console.warn("Filesystem write unavailable (EROFS fallback to data URL):", fsErr?.message || fsErr);
      return NextResponse.json({ ok: true, url: dataUrl });
    }
  } catch (err: any) {
    console.error("Upload error:", err);
    return NextResponse.json(
      { error: err?.message || "Failed to upload file." },
      { status: 500 }
    );
  }
}
