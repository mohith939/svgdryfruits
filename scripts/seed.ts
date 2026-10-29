import { seedRealData } from "../src/lib/seed-catalog";

async function main() {
  await seedRealData();
  process.exit(0);
}

main().catch((err) => {
  console.error("Seeding error:", err);
  process.exit(1);
});
