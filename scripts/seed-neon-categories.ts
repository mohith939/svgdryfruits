import { getDb } from "../src/db/client";
import { categories } from "../src/db/schema";
import { REAL_CATEGORIES } from "../src/lib/catalog-data";

(async () => {
  console.log("Connecting to Neon database...");
  const db = await getDb();
  let count = 0;
  for (const c of REAL_CATEGORIES) {
    await db
      .insert(categories)
      .values({
        id: c.id,
        slug: c.slug,
        name: c.name,
        description: c.description ?? null,
        imageUrl: c.imageUrl ?? null,
        sortOrder: c.sortOrder ?? 0,
        active: true,
      })
      .onConflictDoNothing();
    count++;
  }
  console.log(`Seeded ${count} categories to Neon!`);
  process.exit(0);
})();
