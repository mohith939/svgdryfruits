import { getDb } from "../src/db/client";
import { orders, orderItems, variants } from "../src/db/schema";
import { eq } from "drizzle-orm";

export async function seedOrders() {
  const db = await getDb();
  const vList = await db.select().from(variants).limit(10);
  if (!vList.length) {
    console.log("No variants found, skipping orders seed");
    return;
  }

  const sampleOrders = [
    {
      orderNumber: "SVG1042",
      customerName: "Ananya Reddy",
      customerEmail: "ananya.reddy@example.com",
      customerPhone: "+91 98765 43210",
      totalPaise: 129000,
      subtotalPaise: 129000,
      paymentStatus: "paid",
      fulfillmentStatus: "pending",
      createdAt: new Date("2026-10-25T10:24:00+05:30"),
      itemCount: 3,
    },
    {
      orderNumber: "SVG1041",
      customerName: "Rahul Sharma",
      customerEmail: "rahul.sharma@example.com",
      customerPhone: "+91 98123 45678",
      totalPaise: 78000,
      subtotalPaise: 78000,
      paymentStatus: "paid",
      fulfillmentStatus: "processing",
      createdAt: new Date("2026-10-25T09:17:00+05:30"),
      itemCount: 2,
    },
    {
      orderNumber: "SVG1040",
      customerName: "Priya Nair",
      customerEmail: "priya.nair@example.com",
      customerPhone: "+91 97456 12345",
      totalPaise: 245000,
      subtotalPaise: 245000,
      paymentStatus: "paid",
      fulfillmentStatus: "shipped",
      createdAt: new Date("2026-10-24T20:45:00+05:30"),
      itemCount: 4,
    },
    {
      orderNumber: "SVG1039",
      customerName: "Vikram Rao",
      customerEmail: "vikram.rao@example.com",
      customerPhone: "+91 99887 76655",
      totalPaise: 39900,
      subtotalPaise: 39900,
      paymentStatus: "pending",
      fulfillmentStatus: "pending",
      createdAt: new Date("2026-10-24T18:12:00+05:30"),
      itemCount: 1,
    },
    {
      orderNumber: "SVG1038",
      customerName: "Sneha Kumar",
      customerEmail: "sneha.kumar@example.com",
      customerPhone: "+91 96543 21098",
      totalPaise: 185000,
      subtotalPaise: 185000,
      paymentStatus: "paid",
      fulfillmentStatus: "delivered",
      createdAt: new Date("2026-10-23T16:03:00+05:30"),
      itemCount: 2,
    },
    {
      orderNumber: "SVG1037",
      customerName: "Arjun Verma",
      customerEmail: "arjun.verma@example.com",
      customerPhone: "+91 98711 22334",
      totalPaise: 345000,
      subtotalPaise: 345000,
      paymentStatus: "paid",
      fulfillmentStatus: "delivered",
      createdAt: new Date("2026-10-23T14:20:00+05:30"),
      itemCount: 3,
    },
    {
      orderNumber: "SVG1036",
      customerName: "Meera Patel",
      customerEmail: "meera.patel@example.com",
      customerPhone: "+91 99001 12233",
      totalPaise: 99000,
      subtotalPaise: 99000,
      paymentStatus: "paid",
      fulfillmentStatus: "shipped",
      createdAt: new Date("2026-10-23T11:45:00+05:30"),
      itemCount: 2,
    },
    {
      orderNumber: "SVG1035",
      customerName: "Karthik Subramanian",
      customerEmail: "karthik.s@example.com",
      customerPhone: "+91 98450 99887",
      totalPaise: 156000,
      subtotalPaise: 156000,
      paymentStatus: "paid",
      fulfillmentStatus: "processing",
      createdAt: new Date("2026-10-22T17:30:00+05:30"),
      itemCount: 2,
    },
    {
      orderNumber: "SVG1034",
      customerName: "Divya Nambiar",
      customerEmail: "divya.n@example.com",
      customerPhone: "+91 97312 34567",
      totalPaise: 64900,
      subtotalPaise: 64900,
      paymentStatus: "paid",
      fulfillmentStatus: "delivered",
      createdAt: new Date("2026-10-22T13:15:00+05:30"),
      itemCount: 1,
    },
    {
      orderNumber: "SVG1033",
      customerName: "Rohan Deshmukh",
      customerEmail: "rohan.d@example.com",
      customerPhone: "+91 98220 54321",
      totalPaise: 289000,
      subtotalPaise: 289000,
      paymentStatus: "paid",
      fulfillmentStatus: "delivered",
      createdAt: new Date("2026-10-22T09:40:00+05:30"),
      itemCount: 4,
    },
  ];

  for (const s of sampleOrders) {
    const [existing] = await db.select().from(orders).where(eq(orders.orderNumber, s.orderNumber)).limit(1);
    if (!existing) {
      const [newOrder] = await db.insert(orders).values({
        orderNumber: s.orderNumber,
        trackingToken: "trk_" + Math.random().toString(36).substring(2, 10),
        paymentStatus: s.paymentStatus,
        fulfillmentStatus: s.fulfillmentStatus,
        customerName: s.customerName,
        customerEmail: s.customerEmail,
        customerPhone: s.customerPhone,
        shippingAddress: {
          line1: "123 Green Avenue, Indiranagar",
          city: "Bengaluru",
          state: "Karnataka",
          pincode: "560038",
        },
        subtotalPaise: s.subtotalPaise,
        discountPaise: 0,
        shippingPaise: 0,
        totalPaise: s.totalPaise,
        createdAt: s.createdAt,
      }).returning();

      for (let i = 0; i < s.itemCount; i++) {
        const v = vList[i % vList.length];
        await db.insert(orderItems).values({
          orderId: newOrder.id,
          variantId: v.id,
          productName: v.sku.includes("CAS") ? "Royal Cashews (Kaju)" : v.sku.includes("ALM") ? "California Almonds" : "Premium Pistachios",
          variantLabel: v.label,
          unitPricePaise: Math.round(s.totalPaise / s.itemCount),
          quantity: 1,
        });
      }
    }
  }

  console.log("Seeded sample orders successfully!");
}

seedOrders().then(() => process.exit(0)).catch((e) => {
  console.error(e);
  process.exit(1);
});
