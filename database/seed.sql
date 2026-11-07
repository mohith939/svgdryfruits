-- SVG Dry Fruits - MySQL Seed Data
-- Automatically populates categories, all authentic dry fruits products, pack variants, and shipping rules

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Categories
INSERT INTO `categories` (`id`, `slug`, `name`, `description`, `image_url`, `sort_order`, `active`) VALUES
('a0000001-0000-4000-8000-000000000001', 'nuts', 'Nuts & Kernels', 'Premium whole almonds, crunchy cashews, pistachios, and walnuts.', '/assets/cat-hd-nuts.png', 1, 1),
('a0000001-0000-4000-8000-000000000002', 'dried-fruits', 'Dried Fruits', 'Naturally sweet sun-dried dates, figs, apricots, and raisins.', '/assets/cat-hd-dried-fruits.png', 2, 1),
('a0000001-0000-4000-8000-000000000003', 'seeds', 'Nutrient Seeds', 'Nutrient-dense raw and roasted seeds packed with protein and zinc.', '/assets/cat-hd-seeds.png', 3, 1),
('a0000001-0000-4000-8000-000000000004', 'healthy-mix', 'Healthy Mix', 'Curated energy trail mixes and wholesome nutrient blends.', '/assets/cat-hd-healthy-mix.png', 4, 1),
('a0000001-0000-4000-8000-000000000005', 'combos', 'Combos', 'Curated celebration packs, mixed hampers, and value bundles.', '/assets/cat-hd-gift-boxes.png', 5, 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `description`=VALUES(`description`), `image_url`=VALUES(`image_url`), `active`=1;

-- 2. Products
INSERT INTO `products` (`id`, `category_id`, `slug`, `name`, `description`, `image_url`, `images`, `is_combo`, `active`, `is_demo`) VALUES
('b0000001-0000-4000-8000-000000000001', (SELECT `id` FROM `categories` WHERE `slug` = 'nuts' LIMIT 1), 'pistachios', 'Premium Pistachios', 'Naturally opened, gently slow-roasted jumbo California pistachios. Sourced from trusted growers for unbeatable freshness, rich crunch, and vibrant green kernels.', '/assets/shop-pistachios-hd.jpg', '["/assets/shop-pistachios-hd.jpg"]', 0, 1, 0),
('b0000001-0000-4000-8000-000000000002', (SELECT `id` FROM `categories` WHERE `slug` = 'nuts' LIMIT 1), 'almonds', 'California Almonds', 'Naturally nutritious, crunchy and incredibly versatile — our California Almonds are sourced from trusted farms, carefully selected for their size, taste and quality.', '/assets/pdp-hero-almonds.jpg', '["/assets/pdp-hero-almonds.jpg"]', 0, 1, 0),
('b0000001-0000-4000-8000-000000000003', (SELECT `id` FROM `categories` WHERE `slug` = 'nuts' LIMIT 1), 'cashews', 'Royal Cashews (Kaju)', 'Grade W240 jumbo whole white cashews. Crispy, naturally sweet, and buttery texture, sourced directly from coastal groves.', '/assets/shop-cashews.jpg', '["/assets/shop-cashews.jpg"]', 0, 1, 0),
('b0000001-0000-4000-8000-000000000004', (SELECT `id` FROM `categories` WHERE `slug` = 'nuts' LIMIT 1), 'walnuts', 'Kashmiri Walnuts (Akhrot)', 'Premium extra-light Kashmiri walnut halves. Rich in brain-boosting Omega-3 fatty acids, vacuum packed for maximum crunch.', '/assets/shop-walnuts.jpg', '["/assets/shop-walnuts.jpg"]', 0, 1, 0),
('b0000001-0000-4000-8000-000000000005', (SELECT `id` FROM `categories` WHERE `slug` = 'dried-fruits' LIMIT 1), 'dates', 'Royal Medjool Dates', 'Succulent, soft, and naturally sweet Jumbo King Medjool dates with rich caramel notes and no added sugars.', '/assets/shop-premium-dates.jpg', '["/assets/shop-premium-dates.jpg"]', 0, 1, 0),
('b0000001-0000-4000-8000-000000000006', (SELECT `id` FROM `categories` WHERE `slug` = 'dried-fruits' LIMIT 1), 'figs', 'Dried Afghan Figs (Anjeer)', 'Hand-threaded sun-dried organic Afghan figs with delightful chewiness, high in dietary fibre, calcium, and essential minerals.', '/assets/shop-dried-figs.jpg', '["/assets/shop-dried-figs.jpg"]', 0, 1, 0),
('b0000001-0000-4000-8000-000000000007', (SELECT `id` FROM `categories` WHERE `slug` = 'dried-fruits' LIMIT 1), 'raisins', 'Golden Long Raisins (Kishmish)', 'Naturally shade-dried sweet long green Kishmish from Sangli orchards. Tender, plump, and packed with iron.', '/assets/shop-golden-raisins.jpg', '["/assets/shop-golden-raisins.jpg"]', 0, 1, 0),
('b0000001-0000-4000-8000-000000000008', (SELECT `id` FROM `categories` WHERE `slug` = 'dried-fruits' LIMIT 1), 'apricots', 'Turkish Dried Apricots', 'Authentic sun-ripened Malatya Turkish apricots. Beautiful golden color with luscious velvety texture and mild tartness.', '/assets/shop-dried-apricots.jpg', '["/assets/shop-dried-apricots.jpg"]', 0, 1, 0),
('b0000001-0000-4000-8000-000000000009', (SELECT `id` FROM `categories` WHERE `slug` = 'seeds' LIMIT 1), 'pumpkin-seeds', 'Raw Pumpkin Seeds', 'AAA Grade unsalted shelled green pumpkin seeds. Rich in zinc, magnesium, and plant-based protein for health & fitness.', '/assets/shop-pumpkin-seeds.jpg', '["/assets/shop-pumpkin-seeds.jpg"]', 0, 1, 0),
('b0000001-0000-4000-8000-000000000010', (SELECT `id` FROM `categories` WHERE `slug` = 'seeds' LIMIT 1), 'sunflower-seeds', 'Raw Sunflower Seeds', 'Gently cleaned whole sunflower seeds without shells. Packed with Vitamin E, selenium, and heart-healthy dietary fats.', '/assets/shop-sunflower-seeds.jpg', '["/assets/shop-sunflower-seeds.jpg"]', 0, 1, 0),
('b0000001-0000-4000-8000-000000000011', (SELECT `id` FROM `categories` WHERE `slug` = 'healthy-mix' LIMIT 1), 'healthy-mix', 'Royal Daily Healthy Mix', 'Supercharged energizing trail mix of roasted California almonds, crunchy cashews, pistachios, walnuts, cranberries, and pumpkin seeds.', '/assets/shop-mixed-nuts.jpg', '["/assets/shop-mixed-nuts.jpg"]', 0, 1, 0),
('b0000001-0000-4000-8000-000000000012', (SELECT `id` FROM `categories` WHERE `slug` = 'combos' LIMIT 1), 'festive-gift-box', 'Festive Dry Fruits Gift Box', 'Luxury artisan wooden gift hamper featuring 4 individual air-sealed glass canisters of California Almonds, Royal Cashews, Pistachios, and Afghan Anjeer.', '/assets/mixed-fruit-reference.jpg', '["/assets/mixed-fruit-reference.jpg"]', 1, 1, 0),
('b0000001-0000-4000-8000-000000000013', (SELECT `id` FROM `categories` WHERE `slug` = 'nuts' LIMIT 1), 'organic-walnuts', 'Organic Himalayan Walnuts', 'Certified 100% organic handpicked wild walnuts harvested from pristine Himalayan orchards in Kashmir. Zero additives or preservatives.', '/assets/collection-walnuts.jpg', '["/assets/collection-walnuts.jpg"]', 0, 1, 0)
ON DUPLICATE KEY UPDATE `category_id`=VALUES(`category_id`), `name`=VALUES(`name`), `description`=VALUES(`description`), `image_url`=VALUES(`image_url`);

-- 3. Variants
INSERT INTO `variants` (`id`, `product_id`, `sku`, `label`, `price_paise`, `compare_price_paise`, `stock`, `low_stock_threshold`, `active`) VALUES
-- Pistachios
('c0000001-0001-4000-8000-000000000001', 'b0000001-0000-4000-8000-000000000001', 'PIST-250', '250 g', 39900, 47900, 45, 5, 1),
('c0000001-0002-4000-8000-000000000001', 'b0000001-0000-4000-8000-000000000001', 'PIST-500', '500 g', 76900, 92900, 35, 5, 1),
('c0000001-0003-4000-8000-000000000001', 'b0000001-0000-4000-8000-000000000001', 'PIST-1KG', '1 kg', 149900, 179900, 25, 5, 1),
-- Almonds
('c0000001-0001-4000-8000-000000000002', 'b0000001-0000-4000-8000-000000000002', 'ALM-250', '250 g', 34900, 39900, 50, 5, 1),
('c0000001-0002-4000-8000-000000000002', 'b0000001-0000-4000-8000-000000000002', 'ALM-500', '500 g', 67900, 76900, 40, 5, 1),
('c0000001-0003-4000-8000-000000000002', 'b0000001-0000-4000-8000-000000000002', 'ALM-1KG', '1 kg', 129900, 149900, 30, 5, 1),
-- Cashews
('c0000001-0001-4000-8000-000000000003', 'b0000001-0000-4000-8000-000000000003', 'CAS-250', '250 g', 38900, 44900, 40, 5, 1),
('c0000001-0002-4000-8000-000000000003', 'b0000001-0000-4000-8000-000000000003', 'CAS-500', '500 g', 74900, 87900, 30, 5, 1),
('c0000001-0003-4000-8000-000000000003', 'b0000001-0000-4000-8000-000000000003', 'CAS-1KG', '1 kg', 144900, 169900, 20, 5, 1),
-- Walnuts
('c0000001-0001-4000-8000-000000000004', 'b0000001-0000-4000-8000-000000000004', 'WAL-250', '250 g', 42900, 49900, 35, 5, 1),
('c0000001-0002-4000-8000-000000000004', 'b0000001-0000-4000-8000-000000000004', 'WAL-500', '500 g', 82900, 96900, 25, 5, 1),
('c0000001-0003-4000-8000-000000000004', 'b0000001-0000-4000-8000-000000000004', 'WAL-1KG', '1 kg', 159900, 189900, 15, 5, 1),
-- Dates
('c0000001-0001-4000-8000-000000000005', 'b0000001-0000-4000-8000-000000000005', 'DAT-250', '250 g', 29900, 34900, 45, 5, 1),
('c0000001-0002-4000-8000-000000000005', 'b0000001-0000-4000-8000-000000000005', 'DAT-500', '500 g', 57900, 66900, 30, 5, 1),
('c0000001-0003-4000-8000-000000000005', 'b0000001-0000-4000-8000-000000000005', 'DAT-1KG', '1 kg', 109900, 129900, 20, 5, 1),
-- Figs
('c0000001-0001-4000-8000-000000000006', 'b0000001-0000-4000-8000-000000000006', 'FIG-250', '250 g', 44900, 52900, 35, 5, 1),
('c0000001-0002-4000-8000-000000000006', 'b0000001-0000-4000-8000-000000000006', 'FIG-500', '500 g', 86900, 99900, 25, 5, 1),
('c0000001-0003-4000-8000-000000000006', 'b0000001-0000-4000-8000-000000000006', 'FIG-1KG', '1 kg', 169900, 199900, 15, 5, 1),
-- Raisins
('c0000001-0001-4000-8000-000000000007', 'b0000001-0000-4000-8000-000000000007', 'RAI-250', '250 g', 18900, 22900, 55, 5, 1),
('c0000001-0002-4000-8000-000000000007', 'b0000001-0000-4000-8000-000000000007', 'RAI-500', '500 g', 34900, 41900, 40, 5, 1),
('c0000001-0003-4000-8000-000000000007', 'b0000001-0000-4000-8000-000000000007', 'RAI-1KG', '1 kg', 66900, 79900, 30, 5, 1),
-- Apricots
('c0000001-0001-4000-8000-000000000008', 'b0000001-0000-4000-8000-000000000008', 'APR-250', '250 g', 32900, 38900, 35, 5, 1),
('c0000001-0002-4000-8000-000000000008', 'b0000001-0000-4000-8000-000000000008', 'APR-500', '500 g', 62900, 73900, 25, 5, 1),
('c0000001-0003-4000-8000-000000000008', 'b0000001-0000-4000-8000-000000000008', 'APR-1KG', '1 kg', 119900, 139900, 20, 5, 1),
-- Pumpkin Seeds
('c0000001-0001-4000-8000-000000000009', 'b0000001-0000-4000-8000-000000000009', 'PMP-250', '250 g', 21900, 26900, 45, 5, 1),
('c0000001-0002-4000-8000-000000000009', 'b0000001-0000-4000-8000-000000000009', 'PMP-500', '500 g', 39900, 49900, 35, 5, 1),
('c0000001-0003-4000-8000-000000000009', 'b0000001-0000-4000-8000-000000000009', 'PMP-1KG', '1 kg', 74900, 89900, 25, 5, 1),
-- Sunflower Seeds
('c0000001-0001-4000-8000-000000000010', 'b0000001-0000-4000-8000-000000000010', 'SUN-250', '250 g', 17900, 21900, 40, 5, 1),
('c0000001-0002-4000-8000-000000000010', 'b0000001-0000-4000-8000-000000000010', 'SUN-500', '500 g', 32900, 39900, 30, 5, 1),
('c0000001-0003-4000-8000-000000000010', 'b0000001-0000-4000-8000-000000000010', 'SUN-1KG', '1 kg', 59900, 72900, 20, 5, 1),
-- Daily Healthy Mix
('c0000001-0001-4000-8000-000000000011', 'b0000001-0000-4000-8000-000000000011', 'MIX-250', '250 g', 36900, 42900, 40, 5, 1),
('c0000001-0002-4000-8000-000000000011', 'b0000001-0000-4000-8000-000000000011', 'MIX-500', '500 g', 69900, 82900, 30, 5, 1),
('c0000001-0003-4000-8000-000000000011', 'b0000001-0000-4000-8000-000000000011', 'MIX-1KG', '1 kg', 134900, 159900, 20, 5, 1),
-- Festive Gift Box
('c0000001-0001-4000-8000-000000000012', 'b0000001-0000-4000-8000-000000000012', 'GIFT-1KG', '1 kg Deluxe Hamper', 189900, 229900, 30, 5, 1),
-- Organic Walnuts
('c0000001-0001-4000-8000-000000000013', 'b0000001-0000-4000-8000-000000000013', 'ORG-WAL-250', '250 g', 49900, 57900, 25, 5, 1),
('c0000001-0002-4000-8000-000000000013', 'b0000001-0000-4000-8000-000000000013', 'ORG-WAL-500', '500 g', 94900, 109900, 20, 5, 1)
ON DUPLICATE KEY UPDATE `product_id`=VALUES(`product_id`), `label`=VALUES(`label`), `price_paise`=VALUES(`price_paise`), `compare_price_paise`=VALUES(`compare_price_paise`);

-- 4. Coupons
INSERT INTO `coupons` (`id`, `code`, `name`, `description`, `type`, `value`, `min_subtotal_paise`, `max_discount_paise`, `active`) VALUES
('d0000001-0001-4000-8000-000000000001', 'SVG10', 'Welcome 10% Discount', 'Get 10% off on your dry fruits order above ₹499', 'percentage', 10, 49900, 30000, 1),
('d0000001-0002-4000-8000-000000000002', 'FESTIVE50', 'Festive ₹50 Off', 'Flat ₹50 discount on orders above ₹999', 'flat', 5000, 99900, 5000, 1)
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`);

-- 5. Shipping settings & Store Contact Information
INSERT INTO `shipping_settings` (`id`, `flat_charge_paise`, `free_threshold_paise`, `active`, `contact_phone`, `contact_email`, `whatsapp_number`, `store_address`) VALUES
(1, 4900, 99900, 1, '919059123456', 'svgdryfruits@gmail.com', '919059123456', 'SVG Dry Fruits, Specialist in Premium Dry Fruits, Hyderabad, Telangana, India')
ON DUPLICATE KEY UPDATE `flat_charge_paise`=VALUES(`flat_charge_paise`), `free_threshold_paise`=VALUES(`free_threshold_paise`), `contact_phone`=VALUES(`contact_phone`), `contact_email`=VALUES(`contact_email`), `whatsapp_number`=VALUES(`whatsapp_number`);

-- 6. Shipping Options (Multi-Condition Delivery Rates)
INSERT INTO `shipping_options` (`id`, `name`, `target_states`, `description`, `estimated_days`, `rate_paise`, `rate_1_2kg_paise`, `rate_2kg_above_paise`, `free_above_paise`, `min_subtotal_paise`, `active`, `sort_order`) VALUES
('ship_ts_ap', 'Telangana & Andhra Pradesh Direct Delivery', 'Telangana, Andhra Pradesh', 'Direct surface express dispatch across Telangana & Andhra Pradesh.', '1 – 3 Business Days', 9000, 13000, 15000, 99900, 0, 1, 1),
('ship_south', 'South India Express (KA, TN, KL)', 'Karnataka, Tamil Nadu, Kerala', 'Regional dispatch across neighboring South Indian states.', '2 – 4 Business Days', 10000, 15000, 21000, 149900, 0, 1, 2),
('ship_roi', 'Rest of India Standard', 'ALL', 'National express courier across all other Indian states & union territories.', '3 – 5 Business Days', 10000, 15000, 21000, 199900, 0, 1, 3)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `target_states`=VALUES(`target_states`), `description`=VALUES(`description`), `rate_paise`=VALUES(`rate_paise`), `rate_1_2kg_paise`=VALUES(`rate_1_2kg_paise`), `rate_2kg_above_paise`=VALUES(`rate_2kg_above_paise`), `free_above_paise`=VALUES(`free_above_paise`);

-- 7. Initial Admin User (svgdryfruits@gmail.com / Mohith@123)
-- Hash generated via password_hash('Mohith@123', PASSWORD_BCRYPT)
INSERT INTO `admins` (`id`, `email`, `password_hash`) VALUES
('e0000001-0001-4000-8000-000000000001', 'svgdryfruits@gmail.com', '$2y$10$w85xN8eG6509e5Xw7u7MceBfv033rWJqj1m92Gz7xXN1XmB/LgCiq')
ON DUPLICATE KEY UPDATE `email`=VALUES(`email`);
