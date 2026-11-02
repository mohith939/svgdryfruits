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

                $initAttr = defined('Pdo\Mysql::ATTR_INIT_COMMAND') ? \Pdo\Mysql::ATTR_INIT_COMMAND : 1002;
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    $initAttr                    => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                ];

                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                self::$driver = 'mysql';
                return self::$instance;
            } catch (Throwable $e) {
                // If local development without local MySQL server, fallback to local SQLite
                $sqlitePath = ROOT_PATH . '/database/database.sqlite';
                if (!file_exists($sqlitePath)) {
                    require_once ROOT_PATH . '/database/init_sqlite.php';
                }

                self::$instance = new PDO('sqlite:' . $sqlitePath, null, null, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                self::$driver = 'sqlite';
            }
        }

        return self::$instance;
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
