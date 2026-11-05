-- SVG Dry Fruits - MySQL Database Schema
-- Compatible with MySQL 5.7+ / 8.0+ and MariaDB 10.3+ (Standard cPanel environments)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id` VARCHAR(64) NOT NULL,
  `slug` VARCHAR(128) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `image_url` VARCHAR(255) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Products
CREATE TABLE IF NOT EXISTS `products` (
  `id` VARCHAR(64) NOT NULL,
  `category_id` VARCHAR(64) NULL,
  `slug` VARCHAR(128) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `image_url` VARCHAR(255) NULL,
  `images` JSON NULL,
  `is_combo` TINYINT(1) NOT NULL DEFAULT 0,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_demo` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_products_slug` (`slug`),
  KEY `idx_products_category_id` (`category_id`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Variants (e.g. 250 g, 500 g, 1 kg packs)
CREATE TABLE IF NOT EXISTS `variants` (
  `id` VARCHAR(64) NOT NULL,
  `product_id` VARCHAR(64) NOT NULL,
  `sku` VARCHAR(128) NOT NULL,
  `label` VARCHAR(128) NOT NULL,
  `price_paise` INT NOT NULL,
  `compare_price_paise` INT NULL,
  `stock` INT NOT NULL DEFAULT 50,
  `low_stock_threshold` INT NOT NULL DEFAULT 5,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_variants_sku` (`sku`),
  KEY `idx_variants_product_id` (`product_id`),
  CONSTRAINT `fk_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Coupons
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` VARCHAR(64) NOT NULL,
  `code` VARCHAR(64) NOT NULL,
  `name` VARCHAR(128) NULL,
  `description` TEXT NULL,
  `type` VARCHAR(32) NOT NULL DEFAULT 'percentage', -- 'percentage' or 'flat'
  `value` INT NOT NULL, -- 10 for 10% or paise amount (e.g. 10000 for ₹100 off)
  `min_subtotal_paise` INT NOT NULL DEFAULT 0,
  `max_discount_paise` INT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `times_used` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_coupons_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Orders
CREATE TABLE IF NOT EXISTS `orders` (
  `id` VARCHAR(64) NOT NULL,
  `order_number` VARCHAR(64) NOT NULL,
  `tracking_token` VARCHAR(64) NOT NULL,
  `payment_status` VARCHAR(32) NOT NULL DEFAULT 'pending', -- pending, paid, failed, cod
  `fulfillment_status` VARCHAR(32) NOT NULL DEFAULT 'awaiting_payment', -- awaiting_payment, processing, dispatched, delivered, cancelled
  `customer_name` VARCHAR(128) NOT NULL,
  `customer_email` VARCHAR(128) NULL,
  `customer_phone` VARCHAR(32) NOT NULL,
  `shipping_address` JSON NOT NULL,
  `subtotal_paise` INT NOT NULL,
  `discount_paise` INT NOT NULL DEFAULT 0,
  `shipping_paise` INT NOT NULL DEFAULT 0,
  `total_paise` INT NOT NULL,
  `coupon_code` VARCHAR(64) NULL,
  `cashfree_order_id` VARCHAR(128) NULL,
  `payment_session_id` VARCHAR(255) NULL,
  `courier` VARCHAR(128) NULL,
  `tracking_number` VARCHAR(128) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_orders_order_number` (`order_number`),
  UNIQUE KEY `idx_orders_tracking_token` (`tracking_token`),
  KEY `idx_orders_cashfree_order_id` (`cashfree_order_id`),
  KEY `idx_orders_status` (`payment_status`, `fulfillment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Order Items
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` VARCHAR(64) NOT NULL,
  `order_id` VARCHAR(64) NOT NULL,
  `variant_id` VARCHAR(64) NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `variant_label` VARCHAR(128) NOT NULL,
  `unit_price_paise` INT NOT NULL,
  `quantity` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order_id` (`order_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Shipping Settings
CREATE TABLE IF NOT EXISTS `shipping_settings` (
  `id` INT NOT NULL DEFAULT 1,
  `flat_charge_paise` INT NOT NULL DEFAULT 0,
  `free_threshold_paise` INT NULL DEFAULT 99900,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Admins
CREATE TABLE IF NOT EXISTS `admins` (
  `id` VARCHAR(64) NOT NULL,
  `email` VARCHAR(128) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_admins_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Combo Items (Hampers & Bundles)
CREATE TABLE IF NOT EXISTS `combo_items` (
  `id` VARCHAR(64) NOT NULL,
  `combo_variant_id` VARCHAR(64) NOT NULL,
  `component_variant_id` VARCHAR(64) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_combo_items_combo_variant` (`combo_variant_id`),
  KEY `idx_combo_items_comp_variant` (`component_variant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Refunds
CREATE TABLE IF NOT EXISTS `refunds` (
  `id` VARCHAR(64) NOT NULL,
  `order_id` VARCHAR(64) NOT NULL,
  `amount_paise` INT NOT NULL,
  `note` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_refunds_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

