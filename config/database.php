<?php
/**
 * SVG Dry Fruits - Database Connection Manager
 * Secure PDO Singleton connection supporting MySQL (cPanel) with automatic local SQLite fallback.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;
    private static string $driver = 'mysql';

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            // First attempt MySQL
            try {
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                    DB_HOST,
                    DB_PORT,
                    DB_NAME
                );

                $initAttr = defined('PDO::MYSQL_ATTR_INIT_COMMAND') ? PDO::MYSQL_ATTR_INIT_COMMAND : 1002;
                $multiStatements = defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS') ? PDO::MYSQL_ATTR_MULTI_STATEMENTS : 1000;
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    $initAttr                    => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                    $multiStatements             => true,
                ];

                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                self::$driver = 'mysql';
                self::ensureSchema(self::$instance);
                return self::$instance;
            } catch (Throwable $e) {
                // If on production / live domain, throw MySQL error
                $host = $_SERVER['HTTP_HOST'] ?? '';
                $isLocal = strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false || php_sapi_name() === 'cli' || php_sapi_name() === 'cli-server';
                
                if (!$isLocal) {
                    throw new Exception("MySQL Connection Error: " . $e->getMessage());
                }

                // If local dev without MySQL, fallback to local SQLite
                $sqlitePath = ROOT_PATH . '/database/database.sqlite';
                if (!file_exists($sqlitePath)) {
                    require_once ROOT_PATH . '/database/init_sqlite.php';
                }

                self::$instance = new PDO('sqlite:' . $sqlitePath, null, null, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                self::$driver = 'sqlite';
                self::ensureSchema(self::$instance);
            }
        }

        return self::$instance;
    }

    private static bool $schemaChecked = false;

    public static function ensureSchema(PDO $pdo): void {
        if (self::$schemaChecked) {
            return;
        }
        self::$schemaChecked = true;

        try {
            if (self::$driver === 'mysql') {
                $pdo->exec("
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

                    CREATE TABLE IF NOT EXISTS `refunds` (
                      `id` VARCHAR(64) NOT NULL,
                      `order_id` VARCHAR(64) NOT NULL,
                      `amount_paise` INT NOT NULL,
                      `note` TEXT NULL,
                      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`),
                      KEY `idx_refunds_order_id` (`order_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                    CREATE TABLE IF NOT EXISTS `shadowfax_settings` (
                      `id` INT NOT NULL DEFAULT 1,
                      `api_key` VARCHAR(255) NULL,
                      `environment` VARCHAR(32) NOT NULL DEFAULT 'staging',
                      `base_url` VARCHAR(255) NULL,
                      `pickup_name` VARCHAR(128) NULL,
                      `pickup_phone` VARCHAR(32) NULL,
                      `pickup_address` TEXT NULL,
                      `pickup_city` VARCHAR(64) NULL,
                      `pickup_state` VARCHAR(64) NULL,
                      `pickup_pincode` VARCHAR(16) NULL,
                      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                    INSERT IGNORE INTO `shadowfax_settings` (`id`, `environment`) VALUES (1, 'staging');
                ");

                try {
                    $colCheck = $pdo->query("SHOW COLUMNS FROM `products` LIKE 'sort_order'")->fetch();
                    if (!$colCheck) {
                        $pdo->exec("ALTER TABLE `products` ADD COLUMN `sort_order` INT NOT NULL DEFAULT 0 AFTER `image_url`");
                    }
                } catch (Throwable $e) {}

                try {
                    $colCheck = $pdo->query("SHOW COLUMNS FROM `products` LIKE 'is_featured'")->fetch();
                    if (!$colCheck) {
                        $pdo->exec("ALTER TABLE `products` ADD COLUMN `is_featured` TINYINT(1) NOT NULL DEFAULT 0 AFTER `sort_order`");
                    }
                } catch (Throwable $e) {}
            } else {
                $pdo->exec("
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
                      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    );

                    CREATE TABLE IF NOT EXISTS shadowfax_settings (
                      id INTEGER PRIMARY KEY DEFAULT 1,
                      api_key TEXT,
                      environment TEXT DEFAULT 'staging',
                      base_url TEXT,
                      pickup_name TEXT,
                      pickup_phone TEXT,
                      pickup_address TEXT,
                      pickup_city TEXT,
                      pickup_state TEXT,
                      pickup_pincode TEXT,
                      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    );

                    INSERT OR IGNORE INTO shadowfax_settings (id, environment) VALUES (1, 'staging');
                ");

                try {
                    $cols = $pdo->query("PRAGMA table_info(products)")->fetchAll(PDO::FETCH_COLUMN, 1);
                    if (!in_array('sort_order', $cols)) {
                        $pdo->exec("ALTER TABLE products ADD COLUMN sort_order INTEGER DEFAULT 0");
                    }
                    if (!in_array('is_featured', $cols)) {
                        $pdo->exec("ALTER TABLE products ADD COLUMN is_featured INTEGER DEFAULT 0");
                    }

                    $shipCols = $pdo->query("PRAGMA table_info(shipping_settings)")->fetchAll(PDO::FETCH_COLUMN, 1);
                    if (!in_array('contact_phone', $shipCols)) {
                        $pdo->exec("ALTER TABLE shipping_settings ADD COLUMN contact_phone TEXT DEFAULT '918340956469'");
                    }
                    if (!in_array('contact_email', $shipCols)) {
                        $pdo->exec("ALTER TABLE shipping_settings ADD COLUMN contact_email TEXT DEFAULT 'svgdryfruits@gmail.com'");
                    }
                    if (!in_array('whatsapp_number', $shipCols)) {
                        $pdo->exec("ALTER TABLE shipping_settings ADD COLUMN whatsapp_number TEXT DEFAULT '918340956469'");
                    }
                    if (!in_array('store_address', $shipCols)) {
                        $pdo->exec("ALTER TABLE shipping_settings ADD COLUMN store_address TEXT DEFAULT 'SVG Dry Fruits, Trunk Road, Kavali, Andhra Pradesh'");
                    }
                } catch (Throwable $e) {}
            }

            // Seed initial sort_order and featured status if not yet initialized
            try {
                $maxProductSort = (int)$pdo->query("SELECT MAX(sort_order) FROM products")->fetchColumn();
                if ($maxProductSort === 0) {
                    $allProds = $pdo->query("SELECT id FROM products ORDER BY created_at ASC")->fetchAll(PDO::FETCH_COLUMN);
                    $pIdx = 10;
                    $upStmt = $pdo->prepare("UPDATE products SET sort_order = ? WHERE id = ?");
                    foreach ($allProds as $pId) {
                        $upStmt->execute([$pIdx, $pId]);
                        $pIdx += 10;
                    }
                }

                $featCount = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_featured = 1")->fetchColumn();
                if ($featCount === 0) {
                    $first8 = $pdo->query("SELECT id FROM products WHERE active = 1 ORDER BY sort_order ASC, created_at ASC LIMIT 8")->fetchAll(PDO::FETCH_COLUMN);
                    if (!empty($first8)) {
                        $inClause = implode(',', array_map(fn($id) => $pdo->quote($id), $first8));
                        $pdo->exec("UPDATE products SET is_featured = 1 WHERE id IN ($inClause)");
                    }
                }

                $maxCatSort = (int)$pdo->query("SELECT MAX(sort_order) FROM categories")->fetchColumn();
                if ($maxCatSort === 0) {
                    $allCats = $pdo->query("SELECT id FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
                    $cIdx = 10;
                    $cUpStmt = $pdo->prepare("UPDATE categories SET sort_order = ? WHERE id = ?");
                    foreach ($allCats as $cId) {
                        $cUpStmt->execute([$cIdx, $cId]);
                        $cIdx += 10;
                    }
                }
            } catch (Throwable $e) {}
        } catch (Throwable $e) {
            error_log("Database schema ensure warning: " . $e->getMessage());
        }
    }

    public static function isConnected(): bool {
        try {
            self::getConnection();
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function getDriver(): string {
        return self::$driver;
    }
}
