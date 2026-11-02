<?php
/**
 * SVG Dry Fruits - Main Configuration
 * Handles environment variables, database config, and Cashfree payment credentials.
 */

// Error handling & Output buffering
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
if (!ob_get_level()) {
    ob_start();
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper to load .env file if it exists
function loadEnv(string $path): void {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Strip surrounding quotes
            if (preg_match('/^"(.*)"$/', $value, $m) || preg_match("/^'(.*)'$/", $value, $m)) {
                $value = $m[1];
            }
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Load .env from root of php or parent directory
loadEnv(__DIR__ . '/../.env');
loadEnv(__DIR__ . '/../../.env');

function env(string $key, $default = null) {
    $val = getenv($key);
    if ($val === false) {
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
    return $val;
}

// App Settings
if (!defined('APP_NAME')) define('APP_NAME', 'SVG Dry Fruits');
if (!defined('APP_URL')) define('APP_URL', rtrim(env('NEXT_PUBLIC_SITE_URL', env('APP_URL', 'https://www.svgdryfruits.com')), '/'));
if (!defined('ADMIN_EMAIL')) define('ADMIN_EMAIL', env('ADMIN_BOOTSTRAP_EMAIL', 'svgdryfruits@gmail.com'));
if (!defined('ADMIN_PASSWORD')) define('ADMIN_PASSWORD', env('ADMIN_BOOTSTRAP_PASSWORD', 'Mohith@123'));
if (!defined('WHATSAPP_NUMBER')) define('WHATSAPP_NUMBER', env('NEXT_PUBLIC_WHATSAPP_NUMBER', '918340956469'));
if (!defined('CONTACT_EMAIL')) define('CONTACT_EMAIL', env('NEXT_PUBLIC_CONTACT_EMAIL', 'svgdryfruits@gmail.com'));

// Database Credentials (MySQL)
if (!defined('DB_HOST')) define('DB_HOST', env('DB_HOST', 'localhost'));
if (!defined('DB_PORT')) define('DB_PORT', (int)env('DB_PORT', 3306));
if (!defined('DB_NAME')) define('DB_NAME', env('DB_NAME', 'cpaneluser_svgdryfruits'));
if (!defined('DB_USER')) define('DB_USER', env('DB_USER', 'cpaneluser_svguser'));
if (!defined('DB_PASS')) define('DB_PASS', env('DB_PASS', ''));

// Cashfree PG Settings
if (!defined('CASHFREE_CLIENT_ID')) define('CASHFREE_CLIENT_ID', env('CASHFREE_APP_ID', env('CASHFREE_CLIENT_ID', env('NEXT_PUBLIC_CASHFREE_CLIENT_ID', '1499458c9b9b6e272481a2656918549941'))));
if (!defined('CASHFREE_CLIENT_SECRET')) define('CASHFREE_CLIENT_SECRET', env('CASHFREE_SECRET_KEY', env('CASHFREE_CLIENT_SECRET', '')));
if (!defined('CASHFREE_APP_ID')) define('CASHFREE_APP_ID', CASHFREE_CLIENT_ID);
if (!defined('CASHFREE_SECRET_KEY')) define('CASHFREE_SECRET_KEY', CASHFREE_CLIENT_SECRET);
if (!defined('CASHFREE_ENV')) define('CASHFREE_ENV', strtoupper(env('CASHFREE_ENVIRONMENT', env('NEXT_PUBLIC_CASHFREE_ENVIRONMENT', 'PRODUCTION'))));
if (!defined('CASHFREE_API_VERSION')) define('CASHFREE_API_VERSION', '2026-01-01');

// Base Paths
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
if (!defined('PUBLIC_PATH')) define('PUBLIC_PATH', ROOT_PATH . '/public');
if (!defined('VIEWS_PATH')) define('VIEWS_PATH', ROOT_PATH . '/views');
