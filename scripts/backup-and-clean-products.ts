import fs from "node:fs";
import path from "node:path";
import { getDb } from "../src/db/client";
import {
  comboItems,
  inventoryMovements,
  orderItems,
  products,
  reservations,
  variants,
  orders,
  admins,
} from "../src/db/schema";
import { sql } from "drizzle-orm";

async function backupAndClean() {
  console.log("=== SVG DRY FRUITS: PRODUCT BACKUP & CLEAN SLATE ===");
  const timestamp = new Date().toISOString().replace(/[:.]/g, "-");
  const backupDir = path.join(process.cwd(), "backup", `catalog-backup-${timestamp}`);
  const uploadsBackupDir = path.join(backupDir, "uploads");

  fs.mkdirSync(uploadsBackupDir, { recursive: true });

  const db = await getDb();

  // 1. Fetch current product data
  let currentProducts: any[] = [];
  let currentVariants: any[] = [];
  let currentCombos: any[] = [];
  let ordersCount = 0;
  let adminsCount = 0;

  try {
    currentProducts = await db.select().from(products);
    currentVariants = await db.select().from(variants);
    currentCombos = await db.select().from(comboItems);
    const orderRows = await db.select().from(orders);
    ordersCount = orderRows.length;
    const adminRows = await db.select().from(admins);
    adminsCount = adminRows.length;
  } catch (err) {
    console.warn("Notice reading tables:", err);
  }

  // 2. Backup upload files
  const uploadsDir = path.join(process.cwd(), "public", "uploads");
  const backedUpFiles: string[] = [];

  if (fs.existsSync(uploadsDir)) {
    const files = fs.readdirSync(uploadsDir);
    for (const f of files) {
      if (f.startsWith(".")) continue;
      const src = path.join(uploadsDir, f);
      const dest = path.join(uploadsBackupDir, f);
      if (fs.statSync(src).isFile()) {
        fs.copyFileSync(src, dest);
        backedUpFiles.push(f);
      }
    }
  }

  // 3. Write JSON backup
  const backupData = {
    timestamp,
    counts: {
      products: currentProducts.length,
      variants: currentVariants.length,
      combos: currentCombos.length,
      preservedOrders: ordersCount,
      preservedAdmins: adminsCount,
      backedUpFiles: backedUpFiles.length,
    },
    products: currentProducts,
    variants: currentVariants,
    combos: currentCombos,
    files: backedUpFiles,
  };

  const backupFilePath = path.join(backupDir, "catalog-backup.json");
  fs.writeFileSync(backupFilePath, JSON.stringify(backupData, null, 2), "utf8");

  console.log(`[BACKUP COMPLETE]`);
  console.log(`- Backup Directory: ${backupDir}`);
  console.log(`- Backed up ${currentProducts.length} products, ${currentVariants.length} variants, ${currentCombos.length} combos.`);
  console.log(`- Backed up ${backedUpFiles.length} uploaded files to ${uploadsBackupDir}`);
  console.log(`- Confirmed ${ordersCount} orders and ${adminsCount} admins will be safely preserved.`);

  // 4. Clean only product data from DB
  console.log("\n[CLEANING PRODUCT RECORDS]");

  // A. Disassociate variantId from orderItems so order history is preserved
  try {
    await db.execute(sql`UPDATE "order_items" SET "variant_id" = NULL WHERE "variant_id" IS NOT NULL`);
    console.log("✓ Safely unlinked variant IDs from historical order items (order data preserved)");
  } catch (err) {
    console.warn("Notice unlinking orderItems:", err);
  }

  // B. Delete pending stock reservations
  try {
    await db.delete(reservations);
    console.log("✓ Cleared pending stock reservations");
  } catch (err) {
    console.warn("Notice clearing reservations:", err);
  }

  // C. Delete inventory movements tied to old products
  try {
    await db.delete(inventoryMovements);
    console.log("✓ Cleared inventory movements");
  } catch (err) {
    console.warn("Notice clearing inventoryMovements:", err);
  }

  // D. Delete combo items
  try {
    await db.delete(comboItems);
    console.log("✓ Cleared combo items");
  } catch (err) {
    console.warn("Notice clearing comboItems:", err);
  }

  // E. Delete variants
  try {
    await db.delete(variants);
    console.log("✓ Cleared product variants");
  } catch (err) {
    console.warn("Notice clearing variants:", err);
  }

  // F. Delete products
  try {
    await db.delete(products);
    console.log("✓ Cleared all products");
  } catch (err) {
    console.warn("Notice clearing products:", err);
  }

  // 5. Clean up uploaded files in public/uploads (preserving directory with .gitkeep)
  if (fs.existsSync(uploadsDir)) {
    const files = fs.readdirSync(uploadsDir);
    for (const f of files) {
      if (f.startsWith(".")) continue;
      const target = path.join(uploadsDir, f);
      if (fs.statSync(target).isFile()) {
        fs.unlinkSync(target);
        console.log(`✓ Removed upload file: ${f}`);
      }
    }
    fs.writeFileSync(path.join(uploadsDir, ".gitkeep"), "", "utf8");
  }

  console.log("\n=== PRODUCT DATA CLEANED SUCCESSFULLY ===");
}

backupAndClean()
  .then(() => process.exit(0))
  .catch((err) => {
    console.error("Backup & clean error:", err);
    process.exit(1);
  });
