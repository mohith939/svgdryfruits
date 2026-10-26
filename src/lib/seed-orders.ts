import { getDb } from "@/db/client";
import { orderItems, orders } from "@/db/schema";
import crypto from "node:crypto";

export const REALISTIC_ORDERS = [
  {
    orderNumber: "SVG-1048",
    customerName: "Aarav Sharma",
    customerEmail: "aarav.sharma@gmail.com",
    customerPhone: "9845012345",
    shippingAddress: { street: "42, 12th Main Road, Indiranagar", city: "Bengaluru", state: "Karnataka", pincode: "560038" },
    paymentStatus: "paid" as const,
    fulfillmentStatus: "processing" as const,
    subtotalPaise: 142800,
    discountPaise: 0,
    shippingPaise: 0,
    totalPaise: 142800,
    courier: "Shadowfax Express",
    trackingNumber: "SFX982741928IN",
    createdAt: new Date(Date.now() - 2 * 3600 * 1000), // 2 hours ago
    items: [
      { productName: "California Almonds", variantLabel: "500 g", unitPricePaise: 67900, quantity: 1 },
      { productName: "Royal Cashews (Kaju)", variantLabel: "500 g", unitPricePaise: 74900, quantity: 1 },
    ],
  },
  {
    orderNumber: "SVG-1047",
    customerName: "Priya Nair",
    customerEmail: "priya.nair@outlook.com",
    customerPhone: "9741234567",
    shippingAddress: { street: "Flat 402, Palm Meadows, Whitefield", city: "Bengaluru", state: "Karnataka", pincode: "560066" },
    paymentStatus: "paid" as const,
    fulfillmentStatus: "pending" as const,
    subtotalPaise: 166700,
    discountPaise: 0,
    shippingPaise: 0,
    totalPaise: 166700,
    courier: null,
    trackingNumber: null,
    createdAt: new Date(Date.now() - 5 * 3600 * 1000), // 5 hours ago
    items: [
      { productName: "Premium Pistachios", variantLabel: "250 g", unitPricePaise: 39900, quantity: 2 },
      { productName: "Dried Afghan Figs (Anjeer)", variantLabel: "500 g", unitPricePaise: 86900, quantity: 1 },
    ],
  },
  {
    orderNumber: "SVG-1046",
    customerName: "Rajesh Patel",
    customerEmail: "rajesh.patel@yahoo.com",
    customerPhone: "9820123456",
    shippingAddress: { street: "18, Hill Road, Bandra West", city: "Mumbai", state: "Maharashtra", pincode: "400050" },
    paymentStatus: "paid" as const,
    fulfillmentStatus: "shipped" as const,
    subtotalPaise: 379800,
    discountPaise: 0,
    shippingPaise: 0,
    totalPaise: 379800,
    courier: "BlueDart Express",
    trackingNumber: "BD771928301IN",
    createdAt: new Date(Date.now() - 18 * 3600 * 1000), // 18 hours ago
    items: [
      { productName: "Festive Dry Fruits Gift Box", variantLabel: "1 kg Deluxe Hamper", unitPricePaise: 189900, quantity: 2 },
    ],
  },
  {
    orderNumber: "SVG-1045",
    customerName: "Sneha Reddy",
    customerEmail: "sneha.reddy@gmail.com",
    customerPhone: "9440123456",
    shippingAddress: { street: "Road No. 36, Jubilee Hills", city: "Hyderabad", state: "Telangana", pincode: "500033" },
    paymentStatus: "paid" as const,
    fulfillmentStatus: "delivered" as const,
    subtotalPaise: 162700,
    discountPaise: 0,
    shippingPaise: 0,
    totalPaise: 162700,
    courier: "Delhivery",
    trackingNumber: "DEL55910284IN",
    createdAt: new Date(Date.now() - 28 * 3600 * 1000), // 1 day ago
    items: [
      { productName: "Kashmiri Walnuts (Akhrot)", variantLabel: "500 g", unitPricePaise: 82900, quantity: 1 },
      { productName: "Royal Medjool Dates", variantLabel: "500 g", unitPricePaise: 57900, quantity: 1 },
      { productName: "Raw Pumpkin Seeds", variantLabel: "250 g", unitPricePaise: 21900, quantity: 1 },
    ],
  },
  {
    orderNumber: "SVG-1044",
    customerName: "Vikram Rao",
    customerEmail: "vikram.rao@tcs.com",
    customerPhone: "9900123456",
    shippingAddress: { street: "7th Cross, Malleshwaram", city: "Bengaluru", state: "Karnataka", pincode: "560003" },
    paymentStatus: "paid" as const,
    fulfillmentStatus: "shipped" as const,
    subtotalPaise: 139800,
    discountPaise: 0,
    shippingPaise: 0,
    totalPaise: 139800,
    courier: "Shadowfax Express",
    trackingNumber: "SFX44910283IN",
    createdAt: new Date(Date.now() - 36 * 3600 * 1000), // 1.5 days ago
    items: [
      { productName: "California Almonds", variantLabel: "1 kg", unitPricePaise: 129900, quantity: 1 },
      { productName: "Chia Seeds Organic", variantLabel: "200 g", unitPricePaise: 9900, quantity: 1 },
    ],
  },
  {
    orderNumber: "SVG-1043",
    customerName: "Ananya Iyer",
    customerEmail: "ananya.iyer@gmail.com",
    customerPhone: "9884012345",
    shippingAddress: { street: "23, 4th Main Road, Besant Nagar", city: "Chennai", state: "Tamil Nadu", pincode: "600090" },
    paymentStatus: "paid" as const,
    fulfillmentStatus: "delivered" as const,
    subtotalPaise: 224800,
    discountPaise: 0,
    shippingPaise: 0,
    totalPaise: 224800,
    courier: "BlueDart Express",
    trackingNumber: "BD99281726IN",
    createdAt: new Date(Date.now() - 48 * 3600 * 1000), // 2 days ago
    items: [
      { productName: "Royal Cashews (Kaju)", variantLabel: "1 kg", unitPricePaise: 144900, quantity: 1 },
      { productName: "Dried Afghan Figs (Anjeer)", variantLabel: "500 g", unitPricePaise: 79900, quantity: 1 },
    ],
  },
  {
    orderNumber: "SVG-1042",
    customerName: "Rohan Kapoor",
    customerEmail: "rohan.kapoor@gmail.com",
    customerPhone: "9811098765",
    shippingAddress: { street: "B-12, Greater Kailash 1", city: "New Delhi", state: "Delhi", pincode: "110048" },
    paymentStatus: "paid" as const,
    fulfillmentStatus: "delivered" as const,
    subtotalPaise: 189900,
    discountPaise: 0,
    shippingPaise: 0,
    totalPaise: 189900,
    courier: "Delhivery",
    trackingNumber: "DEL77889911IN",
    createdAt: new Date(Date.now() - 60 * 3600 * 1000), // 2.5 days ago
    items: [
      { productName: "Festive Dry Fruits Gift Box", variantLabel: "1 kg Deluxe Hamper", unitPricePaise: 189900, quantity: 1 },
    ],
  },
  {
    orderNumber: "SVG-1041",
    customerName: "Kavita Menon",
    customerEmail: "kavita.menon@gmail.com",
    customerPhone: "9847123456",
    shippingAddress: { street: "MG Road, Ravipuram", city: "Kochi", state: "Kerala", pincode: "682016" },
    paymentStatus: "paid" as const,
    fulfillmentStatus: "processing" as const,
    subtotalPaise: 114800,
    discountPaise: 0,
    shippingPaise: 0,
    totalPaise: 114800,
    courier: "Shadowfax Express",
    trackingNumber: "SFX12984710IN",
    createdAt: new Date(Date.now() - 72 * 3600 * 1000), // 3 days ago
    items: [
      { productName: "Golden Raisins (Kismis)", variantLabel: "500 g", unitPricePaise: 34900, quantity: 1 },
      { productName: "Premium Pistachios", variantLabel: "500 g", unitPricePaise: 79900, quantity: 1 },
    ],
  },
  {
    orderNumber: "SVG-1040",
    customerName: "Suresh Deshmukh",
    customerEmail: "suresh.deshmukh@gmail.com",
    customerPhone: "9822012345",
    shippingAddress: { street: "FC Road, Shivajinagar", city: "Pune", state: "Maharashtra", pincode: "411005" },
    paymentStatus: "pending" as const,
    fulfillmentStatus: "pending" as const,
    subtotalPaise: 84900,
    discountPaise: 0,
    shippingPaise: 0,
    totalPaise: 84900,
    courier: null,
    trackingNumber: null,
    createdAt: new Date(Date.now() - 84 * 3600 * 1000), // 3.5 days ago
    items: [
      { productName: "Kashmiri Walnuts (Akhrot)", variantLabel: "500 g", unitPricePaise: 84900, quantity: 1 },
    ],
  },
  {
    orderNumber: "SVG-1039",
    customerName: "Meera Joshi",
    customerEmail: "meera.joshi@gmail.com",
    customerPhone: "9879012345",
    shippingAddress: { street: "CG Road, Navrangpura", city: "Ahmedabad", state: "Gujarat", pincode: "380009" },
    paymentStatus: "paid" as const,
    fulfillmentStatus: "delivered" as const,
    subtotalPaise: 154800,
    discountPaise: 0,
    shippingPaise: 0,
    totalPaise: 154800,
    courier: "BlueDart Express",
    trackingNumber: "BD33445566IN",
    createdAt: new Date(Date.now() - 96 * 3600 * 1000), // 4 days ago
    items: [
      { productName: "California Almonds", variantLabel: "500 g", unitPricePaise: 67900, quantity: 1 },
      { productName: "Dried Afghan Figs (Anjeer)", variantLabel: "500 g", unitPricePaise: 86900, quantity: 1 },
    ],
  },
];

export async function seedRealisticOrders() {
  const db = await getDb();

  for (const o of REALISTIC_ORDERS) {
    const trackingToken = "trk_" + crypto.randomBytes(8).toString("hex");
    const [createdOrder] = await db
      .insert(orders)
      .values({
        orderNumber: o.orderNumber,
        customerName: o.customerName,
        customerEmail: o.customerEmail,
        customerPhone: o.customerPhone,
        shippingAddress: o.shippingAddress,
        paymentStatus: o.paymentStatus,
        fulfillmentStatus: o.fulfillmentStatus,
        subtotalPaise: o.subtotalPaise,
        discountPaise: o.discountPaise,
        shippingPaise: o.shippingPaise,
        totalPaise: o.totalPaise,
        courier: o.courier,
        trackingNumber: o.trackingNumber,
        trackingToken,
        createdAt: o.createdAt,
      })
      .onConflictDoNothing()
      .returning();

    if (createdOrder) {
      for (const item of o.items) {
        await db.insert(orderItems).values({
          orderId: createdOrder.id,
          productName: item.productName,
          variantLabel: item.variantLabel,
          unitPricePaise: item.unitPricePaise,
          quantity: item.quantity,
        });
      }
    }
  }

  console.log(`Seeded ${REALISTIC_ORDERS.length} realistic orders successfully!`);
}
