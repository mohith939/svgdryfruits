# SVG Dry Fruits — Online Storefront & Administration

A production-oriented, premium ecommerce website for Indian dry-fruits brand **SVG Dry Fruits**. Built with Next.js 16, React 19, TypeScript, Drizzle ORM, PostgreSQL / embedded PGlite, and Razorpay payment integration.

---

## Key Features

1. **Brand Identity & Storefront**
   - Distinctive warm ivory, deep emerald green (`#073D26`), champagne gold, and cocoa palette.
   - Original SVG Dry Fruits circular logo preserved across header, footer, and admin portal.
   - Fixed-weight pack sizes: **250 g**, **500 g**, and **1 kg** with per-variant stock tracking.
   - Product catalog, search, category browsing, and product details with instant variant price switching.
   - Floating WhatsApp support button (`wa.me`) using `NEXT_PUBLIC_WHATSAPP_NUMBER`.

2. **Cart & Guest Checkout**
   - Persistent guest cart surviving browser refreshes without mandatory customer login.
   - Server-side price, coupon, and inventory re-validation (client prices are never trusted).
   - Dynamic free-shipping qualification bar.
   - Guest checkout collecting structured Indian delivery addresses (PIN code, state, city, street, landmark, mobile number).

3. **Payments & Inventory Reservation**
   - Razorpay payment checkout integration in paise.
   - Atomic database inventory holds (`reserveStock`) with 15-minute TTL to prevent concurrent overselling.
   - Server-side cryptographic HMAC-SHA256 signature verification (`/api/checkout/verify`).
   - Idempotent commit transition (`commitReservations`) ensuring exactly-once inventory deduction.
   - Razorpay webhook listener (`/api/webhooks/razorpay`) with duplicate event deduplication.
   - Safe local simulated test mode when gateway API keys are not yet configured in development.

4. **Order Confirmation & Customer Tracking**
   - Verified post-purchase confirmation page (`/order/success`) providing order receipt and private tracking token.
   - Customer-facing tracking page (`/track` and `/track-order`) with visual order timeline:
     - Placed → Confirmed → Processing → Packed → Dispatched → Delivered.
     - Displays courier name and tracking/AWB number once entered by the administrator.
     - Anti-enumeration protection: requires matching order number + private token or customer mobile phone.

5. **Administrator Portal (`/admin`)**
   - Password-authenticated admin login using salted `scrypt` hashing and secure HTTP-only cookies (`svg_admin_session`).
   - **Overview**: Real-time KPI metrics (Verified Revenue, Total Orders, Orders Awaiting Fulfillment, Low Stock Alerts) and recent order activity.
   - **Products & Stock**: Full catalog management, adding/editing products, updating pack sizes (250 g, 500 g, 1 kg), prices, and stock levels.
   - **Combos & Hampers**: Link single product variants to gift combos so combo purchases automatically deduct component stock.
   - **Orders & Shipping**: Search and filter orders by payment and fulfillment status, update courier tracking info, and manage order lifecycle.
   - **Manual Refund Recording**: Record manual Razorpay refunds with audit notes and optional automatic inventory restocking.
   - **Coupons**: Percentage and flat rupee discounts, cart minimums, usage caps, and active toggles.
   - **Store Settings**: Flat shipping fee, free shipping threshold, and live integration status inspection.

---

## Technology Stack

- **Framework**: Next.js 16 (App Router & Turbopack)
- **UI**: React 19, Semantic HTML5, CSS Custom Properties Design System
- **Language**: TypeScript (strict mode)
- **Database**: Drizzle ORM with embedded local PGlite (`.data/pglite`) for instant zero-dependency local dev, and native PostgreSQL via `pg` when `DATABASE_URL` is set.
- **Payments**: Official `razorpay` SDK with HMAC-SHA256 signature verification.
- **Validation**: Zod schema validation on all route handlers.
- **Testing**: Vitest unit and concurrency test suite.

---

## Getting Started

### 1. Prerequisites
- Node.js 18+ or 20+
- npm

### 2. Installation
```bash
git clone <repo-url>
cd SVG
npm install
```

### 3. Environment Variables
Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```

| Variable | Description |
| :--- | :--- |
| `DATABASE_URL` | Optional PostgreSQL connection string. If blank, uses embedded local PGlite (`.data/pglite`). |
| `NEXT_PUBLIC_SITE_URL` | Public site domain (default: `http://localhost:3000`). |
| `RAZORPAY_KEY_ID` | Razorpay Key ID (test mode: `rzp_test_...`). |
| `RAZORPAY_KEY_SECRET` | Razorpay Key Secret. |
| `RAZORPAY_WEBHOOK_SECRET` | Secret configured in Razorpay Webhooks dashboard. |
| `NEXT_PUBLIC_RAZORPAY_KEY_ID` | Public client Razorpay key ID. |
| `NEXT_PUBLIC_WHATSAPP_NUMBER` | Business WhatsApp mobile number (e.g. `919876543210`). |
| `NEXT_PUBLIC_CONTACT_EMAIL` | Business customer support email. |
| `ADMIN_BOOTSTRAP_EMAIL` | Initial administrator login email. |
| `ADMIN_BOOTSTRAP_PASSWORD` | Initial administrator login password (min 8 characters). |

*(Note: When Razorpay keys are left blank in local development, checkout operates in a simulated test mode, allowing full end-to-end testing without external credentials).*

### 4. Database Seed & Initial Admin Setup
Seed initial demo dry-fruit products:
```bash
npm run db:seed
```

Create or reset an administrator account:
```bash
npx tsx scripts/bootstrap-admin.ts admin@svgdryfruits.com AdminPassword123!
```

### 5. Running Development Server
```bash
npm run dev
```
Open [http://localhost:3000](http://localhost:3000) in your browser.
Access the admin portal at [http://localhost:3000/admin](http://localhost:3000/admin).

---

## Scripts & Verification

- **Typecheck**: `npm run typecheck`
- **Run Tests**: `npm test`
- **Production Build**: `npm run build`
- **Start Production Server**: `npm run start`

---

## Order & Payment Lifecycle Machine

```
Guest Checkout -> Pending Payment (Stock Held with 15m TTL)
                       │
       ┌───────────────┴───────────────┐
       ▼                               ▼
Payment Verified (HMAC Signature)  Payment Failed / Expired
       │                               │
Stock Deducted (Committed)         Hold Released
Status: Confirmed                  Status: Failed
       │
Processing -> Packed -> Shipped (Courier AWB) -> Delivered
       │
Manual Cancellation / Refund -> Optional Inventory Restocked
```
