import { getDb } from "../src/db/client";
import { admins } from "../src/db/schema";
import { hashPassword } from "../src/lib/auth";
import { eq } from "drizzle-orm";

async function main() {
  const args = process.argv.slice(2);
  const email = args[0] || process.env.ADMIN_BOOTSTRAP_EMAIL;
  const password = args[1] || process.env.ADMIN_BOOTSTRAP_PASSWORD;

  if (!email || !password) {
    console.error("Usage: npx tsx scripts/bootstrap-admin.ts <email> <password>");
    process.exit(1);
  }

  if (password.length < 8) {
    console.error("Password must be at least 8 characters long.");
    process.exit(1);
  }

  const db = await getDb();
  const cleanEmail = email.trim().toLowerCase();
  const passwordHash = hashPassword(password);

  const [existing] = await db.select().from(admins).where(eq(admins.email, cleanEmail)).limit(1);

  if (existing) {
    await db.update(admins).set({ passwordHash }).where(eq(admins.id, existing.id));
    console.log(`Updated password for existing administrator: ${cleanEmail}`);
  } else {
    await db.insert(admins).values({
      email: cleanEmail,
      passwordHash,
    });
    console.log(`Successfully created new administrator: ${cleanEmail}`);
  }

  process.exit(0);
}

main().catch((err) => {
  console.error("Bootstrap error:", err);
  process.exit(1);
});
