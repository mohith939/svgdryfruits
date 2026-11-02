# SVG Dry Fruits — Online Storefront & Administration

A modern, high-performance, native PHP ecommerce platform built for Indian luxury dry-fruits brand **SVG Dry Fruits**. Engineered for standard cPanel shared hosting (such as **Sunlight Host**) with native MySQL support, zero Node.js dependencies, clean URL routing, and Cashfree Payment Gateway integration.

---

## 🌟 Key Features

1. **Storefront & Brand Design (1:1 Visual Parity)**
   - Distinctive warm ivory, deep emerald green (`#073D26`), champagne gold, and cocoa palette.
   - Circular SVG Dry Fruits logo across header, footer, and admin portal.
   - Rotating hero dry fruits bowl animation (`heroBowlSpin`).
   - 4 organic floating corner ingredients (`floatTL`, `floatTR`, `floatBL`, `floatBR`).
   - Fixed-weight pack sizes: **250 g**, **500 g**, and **1 kg** with per-variant stock tracking.
   - Dynamic weight pill selection with live price recalculation.
   - Finstics partnership spotlight card in the botanical footer.
   - Floating WhatsApp support button (`wa.me`).

2. **Shopping Bag & Cashfree Checkout**
   - Session-based cart with real-time stock and coupon discount calculation.
   - Live Cashfree Payment Gateway integration (SDK version `2026-01-01` / Drop-in v3 Modal).
   - Cashfree Drop-in popup modal displaying UPI (Google Pay, PhonePe, Paytm, BHIM, QR code), Cards, and Netbanking.
   - Delivery address collection with 6-digit Indian PIN code and 10-digit mobile number validation.
   - Auto-verifying order confirmation screen (`/order-success`).

3. **Order Tracking (`/track`)**
   - Visual timeline: Placed → Confirmed → Processing → Packed → Dispatched → Delivered.
   - Order search by Order Number (`SVG-YYYYMMDD-XXXX`) or Tracking Token.
   - Express courier name and tracking/AWB reference display.

4. **Complete Admin Portal (`/admin`)**
   - Secure session authentication with bcrypt password hashing.
   - **Overview**: 4 KPI cards (Verified Revenue, Total Orders, Awaiting Fulfilment, Low Stock Alerts) + Recent Orders.
   - **Products & Stock**: Full catalog table, stock badges, search/filter toolbar, add/edit product forms.
   - **Combos & Hampers**: Luxury hamper bundle builder with dynamic "Max Packable" inventory calculation and bottleneck indicators.
   - **Categories**: Icon preset selection, category ordering, and active toggles.
   - **Orders & Fulfilment**: Order status filters, customer avatar color themes, 1-Click Shadowfax dispatch, and manual refund recording.
   - **Coupons**: Percentage and flat rupee discounts, cart minimums, usage counters, and expiration controls.
   - **Store Settings**: Flat shipping fee, free shipping threshold, Cashfree PG status, WhatsApp, and database diagnostics.

---

## 🚀 Sunlight Host (cPanel) Deployment Guide

Deploying SVG Dry Fruits on **Sunlight Host** or any standard cPanel web hosting is fast and requires **zero Node.js or npm builds**.

### Step 1: Create a MySQL Database in cPanel

1. Log in to your **Sunlight Host cPanel**.
2. Under the **Databases** section, click **MySQL® Database Wizard**.
3. **Step 1 — Create a Database**:
   - Enter a name, e.g. `svgdryfruits`.
   - Your full database name will be: `cpaneluser_svgdryfruits`.
4. **Step 2 — Create Database User**:
   - Username: e.g. `svguser` (full username: `cpaneluser_svguser`).
   - Password: Click *Password Generator* or enter a strong password. **Save this password.**
5. **Step 3 — Add User to Database**:
   - Check **ALL PRIVILEGES** and click **Make Changes**.

---

### Step 2: Deploy Files via Git or File Manager

#### Option A: Using Git Version Control in cPanel (Recommended)
1. In cPanel, click **Git™ Version Control** → **Create**.
2. **Clone URL**: `https://github.com/mohith939/svgdryfruits.git`
3. **Repository Path**: `/home/cpaneluser/public_html` (or your domain's document root).
4. Click **Create** to clone the repository.

#### Option B: Using cPanel File Manager
1. In cPanel, open **File Manager** and navigate to `public_html`.
2. Upload the repository ZIP file and click **Extract**.
3. Ensure all files (`index.php`, `.htaccess`, `config/`, `views/`, `src/`, `assets/`, etc.) are directly inside `public_html`.

---

### Step 3: Configure Environment Variables (`.env`)

In your cPanel `public_html` directory, create or edit the `.env` file:

```env
# Database Credentials (from Step 1)
DB_HOST=localhost
DB_PORT=3306
DB_NAME=cpaneluser_svgdryfruits
DB_USER=cpaneluser_svguser
DB_PASS=YourDatabasePasswordHere

# Website URL
APP_URL=https://www.svgdryfruits.com
NEXT_PUBLIC_SITE_URL=https://www.svgdryfruits.com

# Cashfree Payments (Production)
CASHFREE_CLIENT_ID=1499458c9b9b6e272481a2656918549941
CASHFREE_CLIENT_SECRET=your_cashfree_secret_key_here
CASHFREE_ENVIRONMENT=PRODUCTION

# Admin Portal Credentials
ADMIN_BOOTSTRAP_EMAIL=svgdryfruits@gmail.com
ADMIN_BOOTSTRAP_PASSWORD=Mohith@123

# Business & Support
NEXT_PUBLIC_WHATSAPP_NUMBER=918340956469
NEXT_PUBLIC_CONTACT_EMAIL=svgdryfruits@gmail.com
```

---

### Step 4: 1-Click Database Setup & Seeding

1. Open your browser and navigate to:
   ```
   https://www.svgdryfruits.com/database/install.php
   ```
2. Click **"Run 1-Click Setup & Seed"**.
3. The installer will automatically:
   - Create all 7 MySQL tables (`categories`, `products`, `variants`, `combos`, `coupons`, `orders`, `order_items`, `shipping_settings`, `admins`).
   - Seed the initial catalog with authentic dry fruits, pack sizes (250g, 500g, 1kg), prices, and images.
   - Provision your secure admin login account.
4. *(Optional for security)* Delete or rename `database/install.php` once setup is complete.

---

### Step 5: Log in to the Admin Dashboard

- Navigate to: **`https://www.svgdryfruits.com/admin`**
- **Email:** `svgdryfruits@gmail.com`
- **Password:** `Mohith@123`

---

## 💻 Local Development Setup

To test or run locally on your computer with PHP's built-in server:

```bash
# Clone the repository
git clone https://github.com/mohith939/svgdryfruits.git
cd svgdryfruits

# Start the local development server (uses auto SQLite fallback if MySQL is not running)
php -S 127.0.0.1:8080 index.php
```

Visit **`http://localhost:8080/`** in your browser.

---

## 📁 Project Directory Structure

```
.
├── .htaccess                 # Apache rewrite rules & security headers
├── .env.example              # Sample environment configuration
├── index.php                 # Front controller & clean URL router
├── api/                      # REST API endpoints
│   ├── cart.php              # Bag add, update, remove, clear
│   ├── checkout.php          # Order creation & Cashfree session
│   ├── coupon.php            # Promo code validation
│   ├── verify.php            # Payment status verification
│   └── webhook.php           # Cashfree webhook listener (HMAC verified)
├── assets/                   # High-res photography, logos, badges (128 files)
├── config/                   # Configuration & drivers
│   ├── config.php            # App constants & env loader
│   ├── database.php          # PDO connection (MySQL + SQLite fallback)
│   └── cashfree.php          # Cashfree PG integration
├── css/                      # CSS styling
│   └── style.css             # Complete design system & keyframe animations
├── database/                 # Database schema & setup
│   ├── schema.sql            # MySQL schema (cPanel)
│   ├── seed.sql              # Initial product catalog seeder
│   ├── install.php           # 1-Click web installer
│   └── init_sqlite.php       # SQLite local dev seeder
├── public/                   # Alternate document root mirror
├── src/                      # Core backend classes
│   ├── Auth.php              # Admin session authentication
│   ├── Cart.php              # Session shopping cart
│   ├── Helpers.php           # Currency, buffering, routing helpers
│   ├── Icon.php              # SVG icon library
│   └── Models.php            # Database query layer & stock manager
└── views/                    # Template views
    ├── home.php              # Homepage with rotating bowl animation
    ├── shop.php              # Full product catalog
    ├── product.php           # Product detail view with weight pills
    ├── cart.php              # Shopping bag view
    ├── checkout.php          # Checkout form with Cashfree Drop-in
    ├── order_success.php     # Order confirmation & receipt
    ├── track.php             # Customer order tracking
    ├── admin/                # 10 Admin management screens
    │   ├── dashboard.php     # Revenue & order KPIs
    │   ├── products.php      # Products & stock management
    │   ├── product_form.php  # Add & edit product
    │   ├── categories.php    # Category builder
    │   ├── combos.php        # Luxury hamper bundle builder
    │   ├── orders.php        # Orders list & status updater
    │   ├── order_detail.php  # Order snapshot & dispatch
    │   ├── coupons.php       # Promo codes
    │   ├── settings.php      # Store settings & shipping fees
    │   └── login.php         # Admin login
    ├── layouts/              # Header, footer, and admin navigation
    └── pages/                # Terms, Privacy, Shipping, Return policies
```

---

## 🔒 Security Best Practices

- **Zero Client-Side Pricing Trust**: Prices and discounts are recalculated strictly on the server against the database.
- **Cashfree Webhook HMAC Signature**: Webhook payloads are verified using SHA-256 HMAC and a 5-minute replay-attack protection window.
- **Directory Protection**: Root `.htaccess` strictly forbids public access to `.env`, `.git`, `config/`, `src/`, `views/`, and `.sql` files.
- **Inventory Concurrency Protection**: Stock is checked and atomically deducted during checkout.
