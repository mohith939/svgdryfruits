import { getDb } from "../src/db/client";
import { categories, comboItems, orderItems, products, reservations, variants } from "../src/db/schema";

export async function wipeEntireCatalog() {
  const db = await getDb();

  console.log("Wiping catalog...");

  // 1. Delete reservations and order items referencing variants
  await db.delete(reservations);
  await db.delete(orderItems);

  // 2. Delete combos & variants
  await db.delete(comboItems);
  await db.delete(variants);

  // 3. Delete products
  await db.delete(products);

  // 4. Delete categories
  await db.delete(categories);

  console.log("Entire catalog successfully wiped clean (0 products, 0 categories).");
}

async function main() {
  await wipeEntireCatalog();
  process.exit(0);
}

if (process.argv[1]?.endsWith("wipe.ts")) {
  main().catch((err) => {
    console.error("Wipe catalog error:", err);
    process.exit(1);
  });
}
