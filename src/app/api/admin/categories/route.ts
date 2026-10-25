import { NextResponse } from "next/server";
import { asc, eq, sql } from "drizzle-orm";
import { z } from "zod";
import { getDb } from "@/db/client";
import { categories, products } from "@/db/schema";
import { getAdminSession } from "@/lib/auth";

// Ensure column exists
async function ensureColumn() {
  const db = await getDb();
  await db.execute(sql`ALTER TABLE "categories" ADD COLUMN IF NOT EXISTS "image_url" text;`);
}

export async function GET() {
  try {
    const session = await getAdminSession();
    if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

    await ensureColumn();
    const db = await getDb();

    const cats = await db
      .select({
        id: categories.id,
        slug: categories.slug,
        name: categories.name,
        description: categories.description,
        imageUrl: categories.imageUrl,
        sortOrder: categories.sortOrder,
        active: categories.active,
        createdAt: categories.createdAt,
        productCount: sql<number>`count(${products.id})::int`,
      })
      .from(categories)
      .leftJoin(products, eq(products.categoryId, categories.id))
      .groupBy(categories.id)
      .orderBy(asc(categories.sortOrder), asc(categories.name));

    return NextResponse.json({ categories: cats });
  } catch (err: any) {
    console.error("GET categories error:", err);
    return NextResponse.json({ error: err?.message || "Failed to load categories" }, { status: 500 });
  }
}

const createSchema = z.object({
  name: z.string().min(2).max(60),
  slug: z.string().min(2).max(60).regex(/^[a-z0-9-]+$/),
  description: z.string().max(250).optional(),
  imageUrl: z.string().optional().nullable(),
  sortOrder: z.number().int().default(0),
  active: z.boolean().default(true),
});

export async function POST(req: Request) {
  try {
    const session = await getAdminSession();
    if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

    const body = await req.json().catch(() => null);
    const parsed = createSchema.safeParse(body);
    if (!parsed.success) {
      return NextResponse.json({ error: "Invalid category payload", details: parsed.error.format() }, { status: 400 });
    }

    await ensureColumn();
    const db = await getDb();

    // Check slug uniqueness
    const [existing] = await db.select().from(categories).where(eq(categories.slug, parsed.data.slug)).limit(1);
    if (existing) {
      return NextResponse.json({ error: "A category with this URL slug already exists." }, { status: 400 });
    }

    const [inserted] = await db
      .insert(categories)
      .values({
        name: parsed.data.name.trim(),
        slug: parsed.data.slug.trim(),
        description: parsed.data.description?.trim() || null,
        imageUrl: parsed.data.imageUrl || null,
        sortOrder: parsed.data.sortOrder,
        active: parsed.data.active,
      })
      .returning();

    return NextResponse.json({ ok: true, category: inserted });
  } catch (err: any) {
    console.error("POST category error:", err);
    return NextResponse.json({ error: err?.message || "Failed to create category" }, { status: 500 });
  }
}

const updateSchema = z.object({
  id: z.string().uuid(),
  name: z.string().min(2).max(60).optional(),
  slug: z.string().min(2).max(60).regex(/^[a-z0-9-]+$/).optional(),
  description: z.string().max(250).optional().nullable(),
  imageUrl: z.string().optional().nullable(),
  sortOrder: z.number().int().optional(),
  active: z.boolean().optional(),
});

export async function PUT(req: Request) {
  try {
    const session = await getAdminSession();
    if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

    const body = await req.json().catch(() => null);
    const parsed = updateSchema.safeParse(body);
    if (!parsed.success) {
      return NextResponse.json({ error: "Invalid update payload" }, { status: 400 });
    }

    await ensureColumn();
    const db = await getDb();

    const { id, ...data } = parsed.data;
    const [updated] = await db.update(categories).set(data).where(eq(categories.id, id)).returning();

    return NextResponse.json({ ok: true, category: updated });
  } catch (err: any) {
    console.error("PUT category error:", err);
    return NextResponse.json({ error: err?.message || "Failed to update category" }, { status: 500 });
  }
}

export async function DELETE(req: Request) {
  try {
    const session = await getAdminSession();
    if (!session) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

    const { searchParams } = new URL(req.url);
    const id = searchParams.get("id");
    if (!id) return NextResponse.json({ error: "Category ID required" }, { status: 400 });

    const db = await getDb();

    // Check if products attached
    const attached = await db.select().from(products).where(eq(products.categoryId, id)).limit(1);
    if (attached.length > 0) {
      // Unlink category from products before deleting
      await db.update(products).set({ categoryId: null }).where(eq(products.categoryId, id));
    }

    await db.delete(categories).where(eq(categories.id, id));
    return NextResponse.json({ ok: true });
  } catch (err: any) {
    console.error("DELETE category error:", err);
    return NextResponse.json({ error: err?.message || "Failed to delete category" }, { status: 500 });
  }
}
