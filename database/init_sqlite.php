<?php
/**
 * SVG Dry Fruits - SQLite Auto-Initializer for Local Testing
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$sqliteFile = __DIR__ . '/database.sqlite';

$pdo = new PDO('sqlite:' . $sqliteFile, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// 1. Create tables
$pdo->exec("
CREATE TABLE IF NOT EXISTS categories (
  id TEXT PRIMARY KEY,
  slug TEXT UNIQUE NOT NULL,
  name TEXT NOT NULL,
  description TEXT,
  image_url TEXT,
  sort_order INTEGER DEFAULT 0,
  active INTEGER DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
  id TEXT PRIMARY KEY,
  category_id TEXT,
  slug TEXT UNIQUE NOT NULL,
  name TEXT NOT NULL,
  description TEXT,
  image_url TEXT,
  images TEXT,
  is_combo INTEGER DEFAULT 0,
  active INTEGER DEFAULT 1,
  is_demo INTEGER DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS variants (
  id TEXT PRIMARY KEY,
  product_id TEXT NOT NULL,
  sku TEXT UNIQUE NOT NULL,
  label TEXT NOT NULL,
  price_paise INTEGER NOT NULL,
  compare_price_paise INTEGER,
  stock INTEGER DEFAULT 50,
  low_stock_threshold INTEGER DEFAULT 5,
  active INTEGER DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS coupons (
  id TEXT PRIMARY KEY,
  code TEXT UNIQUE NOT NULL,
  name TEXT,
  description TEXT,
  type TEXT DEFAULT 'percentage',
  value INTEGER NOT NULL,
  min_subtotal_paise INTEGER DEFAULT 0,
  max_discount_paise INTEGER,
  active INTEGER DEFAULT 1,
  times_used INTEGER DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
  id TEXT PRIMARY KEY,
  order_number TEXT UNIQUE NOT NULL,
  tracking_token TEXT UNIQUE NOT NULL,
  payment_status TEXT DEFAULT 'pending',
  fulfillment_status TEXT DEFAULT 'awaiting_payment',
  customer_name TEXT NOT NULL,
  customer_email TEXT,
  customer_phone TEXT NOT NULL,
  shipping_address TEXT NOT NULL,
  subtotal_paise INTEGER NOT NULL,
  discount_paise INTEGER DEFAULT 0,
  shipping_paise INTEGER DEFAULT 0,
  total_paise INTEGER NOT NULL,
  coupon_code TEXT,
  shipping_option_id TEXT,
  shipping_option_name TEXT,
  cashfree_order_id TEXT,
  payment_session_id TEXT,
  stock_restored INTEGER DEFAULT 0,
  courier TEXT,
  tracking_number TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS order_items (
  id TEXT PRIMARY KEY,
  order_id TEXT NOT NULL,
  variant_id TEXT,
  product_name TEXT NOT NULL,
  variant_label TEXT NOT NULL,
  unit_price_paise INTEGER NOT NULL,
  quantity INTEGER NOT NULL,
  stock_restored INTEGER DEFAULT 0,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS shipping_settings (
  id INTEGER PRIMARY KEY DEFAULT 1,
  flat_charge_paise INTEGER DEFAULT 0,
  free_threshold_paise INTEGER DEFAULT 99900,
  active INTEGER DEFAULT 1,
  contact_phone TEXT DEFAULT '919059123456',
  contact_email TEXT DEFAULT 'svgdryfruits@gmail.com',
  whatsapp_number TEXT DEFAULT '919059123456',
  store_address TEXT DEFAULT 'SVG Dry Fruits, Hyderabad, India'
);

CREATE TABLE IF NOT EXISTS shipping_options (
  id TEXT PRIMARY KEY,
  name TEXT NOT NULL,
  description TEXT,
  estimated_days TEXT,
  rate_paise INTEGER DEFAULT 4900,
  free_above_paise INTEGER,
  min_subtotal_paise INTEGER DEFAULT 0,
  active INTEGER DEFAULT 1,
  sort_order INTEGER DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admins (
  id TEXT PRIMARY KEY,
  email TEXT UNIQUE NOT NULL,
  password_hash TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS combo_items (
  id TEXT PRIMARY KEY,
  combo_variant_id TEXT NOT NULL,
  component_variant_id TEXT NOT NULL,
  quantity INTEGER DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS refunds (
  id TEXT PRIMARY KEY,
  order_id TEXT NOT NULL,
  amount_paise INTEGER NOT NULL,
  note TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);
");


// 2. Seed Categories
$categories = [
    ['a0000001-0000-4000-8000-000000000001', 'nuts', 'Nuts & Kernels', 'Premium whole almonds, crunchy cashews, pistachios, and walnuts.', '/assets/cat-hd-nuts.png', 1],
    ['a0000001-0000-4000-8000-000000000002', 'dried-fruits', 'Dried Fruits', 'Naturally sweet sun-dried dates, figs, apricots, and raisins.', '/assets/cat-hd-dried-fruits.png', 2],
    ['a0000001-0000-4000-8000-000000000003', 'seeds', 'Nutrient Seeds', 'Nutrient-dense raw and roasted seeds packed with protein and zinc.', '/assets/cat-hd-seeds.png', 3],
    ['a0000001-0000-4000-8000-000000000004', 'healthy-mix', 'Healthy Mix', 'Curated energy trail mixes and wholesome nutrient blends.', '/assets/cat-hd-healthy-mix.png', 4],
    ['a0000001-0000-4000-8000-000000000005', 'combos', 'Combos', 'Curated celebration packs, mixed hampers, and value bundles.', '/assets/cat-hd-gift-boxes.png', 5],
];

$catStmt = $pdo->prepare("INSERT OR REPLACE INTO categories (id, slug, name, description, image_url, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($categories as $cat) {
    $catStmt->execute($cat);
}

// 3. Seed Products
$products = [
    ['b0000001-0000-4000-8000-000000000001', 'a0000001-0000-4000-8000-000000000001', 'pistachios', 'Premium Pistachios', 'Naturally opened, gently slow-roasted jumbo California pistachios. Sourced from trusted growers for unbeatable freshness, rich crunch, and vibrant green kernels.', '/assets/shop-pistachios-hd.jpg', 0],
    ['b0000001-0000-4000-8000-000000000002', 'a0000001-0000-4000-8000-000000000001', 'almonds', 'California Almonds', 'Naturally nutritious, crunchy and incredibly versatile — our California Almonds are sourced from trusted farms, carefully selected for their size, taste and quality.', '/assets/pdp-hero-almonds.jpg', 0],
    ['b0000001-0000-4000-8000-000000000003', 'a0000001-0000-4000-8000-000000000001', 'cashews', 'Royal Cashews (Kaju)', 'Grade W240 jumbo whole white cashews. Crispy, naturally sweet, and buttery texture, sourced directly from coastal groves.', '/assets/shop-cashews.jpg', 0],
    ['b0000001-0000-4000-8000-000000000004', 'a0000001-0000-4000-8000-000000000001', 'walnuts', 'Kashmiri Walnuts (Akhrot)', 'Premium extra-light Kashmiri walnut halves. Rich in brain-boosting Omega-3 fatty acids, vacuum packed for maximum crunch.', '/assets/shop-walnuts.jpg', 0],
    ['b0000001-0000-4000-8000-000000000005', 'a0000001-0000-4000-8000-000000000002', 'dates', 'Royal Medjool Dates', 'Succulent, soft, and naturally sweet Jumbo King Medjool dates with rich caramel notes and no added sugars.', '/assets/shop-premium-dates.jpg', 0],
    ['b0000001-0000-4000-8000-000000000006', 'a0000001-0000-4000-8000-000000000002', 'figs', 'Dried Afghan Figs (Anjeer)', 'Hand-threaded sun-dried organic Afghan figs with delightful chewiness, high in dietary fibre, calcium, and essential minerals.', '/assets/shop-dried-figs.jpg', 0],
    ['b0000001-0000-4000-8000-000000000007', 'a0000001-0000-4000-8000-000000000002', 'raisins', 'Golden Long Raisins (Kishmish)', 'Naturally shade-dried sweet long green Kishmish from Sangli orchards. Tender, plump, and packed with iron.', '/assets/shop-golden-raisins.jpg', 0],
    ['b0000001-0000-4000-8000-000000000008', 'a0000001-0000-4000-8000-000000000002', 'apricots', 'Turkish Dried Apricots', 'Authentic sun-ripened Malatya Turkish apricots. Beautiful golden color with luscious velvety texture and mild tartness.', '/assets/shop-dried-apricots.jpg', 0],
    ['b0000001-0000-4000-8000-000000000009', 'a0000001-0000-4000-8000-000000000003', 'pumpkin-seeds', 'Raw Pumpkin Seeds', 'AAA Grade unsalted shelled green pumpkin seeds. Rich in zinc, magnesium, and plant-based protein for health & fitness.', '/assets/shop-pumpkin-seeds.jpg', 0],
    ['b0000001-0000-4000-8000-000000000010', 'a0000001-0000-4000-8000-000000000003', 'sunflower-seeds', 'Raw Sunflower Seeds', 'Gently cleaned whole sunflower seeds without shells. Packed with Vitamin E, selenium, and heart-healthy dietary fats.', '/assets/shop-sunflower-seeds.jpg', 0],
    ['b0000001-0000-4000-8000-000000000011', 'a0000001-0000-4000-8000-000000000004', 'healthy-mix', 'Royal Daily Healthy Mix', 'Supercharged energizing trail mix of roasted California almonds, crunchy cashews, pistachios, walnuts, cranberries, and pumpkin seeds.', '/assets/shop-mixed-nuts.jpg', 0],
    ['b0000001-0000-4000-8000-000000000012', 'a0000001-0000-4000-8000-000000000005', 'festive-gift-box', 'Festive Dry Fruits Gift Box', 'Luxury artisan wooden gift hamper featuring 4 individual air-sealed glass canisters of California Almonds, Royal Cashews, Pistachios, and Afghan Anjeer.', '/assets/mixed-fruit-reference.jpg', 1],
    ['b0000001-0000-4000-8000-000000000013', 'a0000001-0000-4000-8000-000000000001', 'organic-walnuts', 'Organic Himalayan Walnuts', 'Certified 100% organic handpicked wild walnuts harvested from pristine Himalayan orchards in Kashmir. Zero additives or preservatives.', '/assets/collection-walnuts.jpg', 0],
];

$prodStmt = $pdo->prepare("INSERT OR REPLACE INTO products (id, category_id, slug, name, description, image_url, is_combo, active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
foreach ($products as $p) {
    $prodStmt->execute($p);
}

// 4. Seed Variants
$variants = [
    // Pistachios
    ['c0000001-0001-4000-8000-000000000001', 'b0000001-0000-4000-8000-000000000001', 'PIST-250', '250 g', 39900, 47900, 45],
    ['c0000001-0002-4000-8000-000000000001', 'b0000001-0000-4000-8000-000000000001', 'PIST-500', '500 g', 76900, 92900, 35],
    ['c0000001-0003-4000-8000-000000000001', 'b0000001-0000-4000-8000-000000000001', 'PIST-1KG', '1 kg', 149900, 179900, 25],
    // Almonds
    ['c0000001-0001-4000-8000-000000000002', 'b0000001-0000-4000-8000-000000000002', 'ALM-250', '250 g', 34900, 39900, 50],
    ['c0000001-0002-4000-8000-000000000002', 'b0000001-0000-4000-8000-000000000002', 'ALM-500', '500 g', 67900, 76900, 40],
    ['c0000001-0003-4000-8000-000000000002', 'b0000001-0000-4000-8000-000000000002', 'ALM-1KG', '1 kg', 129900, 149900, 30],
    // Cashews
    ['c0000001-0001-4000-8000-000000000003', 'b0000001-0000-4000-8000-000000000003', 'CAS-250', '250 g', 38900, 44900, 40],
    ['c0000001-0002-4000-8000-000000000003', 'b0000001-0000-4000-8000-000000000003', 'CAS-500', '500 g', 74900, 87900, 30],
    ['c0000001-0003-4000-8000-000000000003', 'b0000001-0000-4000-8000-000000000003', 'CAS-1KG', '1 kg', 144900, 169900, 20],
    // Walnuts
    ['c0000001-0001-4000-8000-000000000004', 'b0000001-0000-4000-8000-000000000004', 'WAL-250', '250 g', 42900, 49900, 35],
    ['c0000001-0002-4000-8000-000000000004', 'b0000001-0000-4000-8000-000000000004', 'WAL-500', '500 g', 82900, 96900, 25],
    ['c0000001-0003-4000-8000-000000000004', 'b0000001-0000-4000-8000-000000000004', 'WAL-1KG', '1 kg', 159900, 189900, 15],
    // Dates
    ['c0000001-0001-4000-8000-000000000005', 'b0000001-0000-4000-8000-000000000005', 'DAT-250', '250 g', 29900, 34900, 45],
    ['c0000001-0002-4000-8000-000000000005', 'b0000001-0000-4000-8000-000000000005', 'DAT-500', '500 g', 57900, 66900, 30],
    ['c0000001-0003-4000-8000-000000000005', 'b0000001-0000-4000-8000-000000000005', 'DAT-1KG', '1 kg', 109900, 129900, 20],
    // Figs
    ['c0000001-0001-4000-8000-000000000006', 'b0000001-0000-4000-8000-000000000006', 'FIG-250', '250 g', 44900, 52900, 35],
    ['c0000001-0002-4000-8000-000000000006', 'b0000001-0000-4000-8000-000000000006', 'FIG-500', '500 g', 86900, 99900, 25],
    ['c0000001-0003-4000-8000-000000000006', 'b0000001-0000-4000-8000-000000000006', 'FIG-1KG', '1 kg', 169900, 199900, 15],
    // Raisins
    ['c0000001-0001-4000-8000-000000000007', 'b0000001-0000-4000-8000-000000000007', 'RAI-250', '250 g', 18900, 22900, 55],
    ['c0000001-0002-4000-8000-000000000007', 'b0000001-0000-4000-8000-000000000007', 'RAI-500', '500 g', 34900, 41900, 40],
    ['c0000001-0003-4000-8000-000000000007', 'b0000001-0000-4000-8000-000000000007', 'RAI-1KG', '1 kg', 66900, 79900, 30],
    // Apricots
    ['c0000001-0001-4000-8000-000000000008', 'b0000001-0000-4000-8000-000000000008', 'APR-250', '250 g', 32900, 38900, 35],
    ['c0000001-0002-4000-8000-000000000008', 'b0000001-0000-4000-8000-000000000008', 'APR-500', '500 g', 62900, 73900, 25],
    ['c0000001-0003-4000-8000-000000000008', 'b0000001-0000-4000-8000-000000000008', 'APR-1KG', '1 kg', 119900, 139900, 20],
    // Pumpkin Seeds
    ['c0000001-0001-4000-8000-000000000009', 'b0000001-0000-4000-8000-000000000009', 'PMP-250', '250 g', 21900, 26900, 45],
    ['c0000001-0002-4000-8000-000000000009', 'b0000001-0000-4000-8000-000000000009', 'PMP-500', '500 g', 39900, 49900, 35],
    ['c0000001-0003-4000-8000-000000000009', 'b0000001-0000-4000-8000-000000000009', 'PMP-1KG', '1 kg', 74900, 89900, 25],
    // Sunflower Seeds
    ['c0000001-0001-4000-8000-000000000010', 'b0000001-0000-4000-8000-000000000010', 'SUN-250', '250 g', 17900, 21900, 40],
    ['c0000001-0002-4000-8000-000000000010', 'b0000001-0000-4000-8000-000000000010', 'SUN-500', '500 g', 32900, 39900, 30],
    ['c0000001-0003-4000-8000-000000000010', 'b0000001-0000-4000-8000-000000000010', 'SUN-1KG', '1 kg', 59900, 72900, 20],
    // Healthy Mix
    ['c0000001-0001-4000-8000-000000000011', 'b0000001-0000-4000-8000-000000000011', 'MIX-250', '250 g', 36900, 42900, 40],
    ['c0000001-0002-4000-8000-000000000011', 'b0000001-0000-4000-8000-000000000011', 'MIX-500', '500 g', 69900, 82900, 30],
    ['c0000001-0003-4000-8000-000000000011', 'b0000001-0000-4000-8000-000000000011', 'MIX-1KG', '1 kg', 134900, 159900, 20],
    // Gift Box
    ['c0000001-0001-4000-8000-000000000012', 'b0000001-0000-4000-8000-000000000012', 'GIFT-1KG', '1 kg Deluxe Hamper', 189900, 229900, 30],
    // Organic Walnuts
    ['c0000001-0001-4000-8000-000000000013', 'b0000001-0000-4000-8000-000000000013', 'ORG-WAL-250', '250 g', 49900, 57900, 25],
    ['c0000001-0002-4000-8000-000000000013', 'b0000001-0000-4000-8000-000000000013', 'ORG-WAL-500', '500 g', 94900, 109900, 20],
];

$varStmt = $pdo->prepare("INSERT OR REPLACE INTO variants (id, product_id, sku, label, price_paise, compare_price_paise, stock, active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
foreach ($variants as $v) {
    $varStmt->execute($v);
}

// 5. Seed Coupons
$pdo->exec("
INSERT OR REPLACE INTO coupons (id, code, name, description, type, value, min_subtotal_paise, max_discount_paise, active) VALUES
('d0000001-0001-4000-8000-000000000001', 'SVG10', 'Welcome 10% Discount', '10% off on dry fruits', 'percentage', 10, 49900, 30000, 1),
('d0000001-0002-4000-8000-000000000002', 'FESTIVE50', 'Festive ₹50 Off', 'Flat ₹50 discount', 'flat', 5000, 99900, 5000, 1);
");

// 6. Seed Shipping Settings & Options
$pdo->exec("
INSERT OR REPLACE INTO shipping_settings (id, flat_charge_paise, free_threshold_paise, active, contact_phone, contact_email, whatsapp_number, store_address) 
VALUES (1, 4900, 99900, 1, '919059123456', 'svgdryfruits@gmail.com', '919059123456', 'SVG Dry Fruits, Hyderabad, India');

INSERT OR REPLACE INTO shipping_options (id, name, description, estimated_days, rate_paise, free_above_paise, min_subtotal_paise, active, sort_order) VALUES
('ship_standard', 'Standard Delivery', 'Carefully packed & shipped via surface express.', '3 – 5 Business Days', 4900, 99900, 0, 1, 1),
('ship_express', 'Priority Air Express', 'Faster delivery via dedicated air cargo.', '1 – 2 Business Days', 12900, 199900, 0, 1, 2);
");

// 7. Seed Admin
$adminPass = password_hash(ADMIN_PASSWORD, PASSWORD_BCRYPT);
$admStmt = $pdo->prepare("INSERT OR REPLACE INTO admins (id, email, password_hash) VALUES ('e0000001-0001-4000-8000-000000000001', ?, ?)");
$admStmt->execute([strtolower(ADMIN_EMAIL), $adminPass]);

echo "SQLite database successfully initialized and seeded with all dry fruits products!\n";
