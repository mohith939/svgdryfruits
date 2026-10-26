import { seedRealisticOrders } from "../src/lib/seed-orders";

async function main() {
  await seedRealisticOrders();
  process.exit(0);
}

main().catch((err) => {
  console.error("Orders seed error:", err);
  process.exit(1);
});
