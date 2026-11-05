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
                ");
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
                ");
            }
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
