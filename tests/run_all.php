<?php
/**
 * SVG Dry Fruits - Automated Test Suite Runner
 * Executes security, business logic, pricing, inventory, and order tests.
 */
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Models.php';
require_once ROOT_PATH . '/src/Cart.php';
require_once ROOT_PATH . '/src/Auth.php';

// Initialize session if not active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class TestSuite {
    private static int $passed = 0;
    private static int $failed = 0;
    private static array $errors = [];

    public static function assert(bool $condition, string $testName, string $details = ''): void {
        if ($condition) {
            self::$passed++;
            echo "  \033[32m✔ PASS:\033[0m {$testName}\n";
        } else {
            self::$failed++;
            $msg = "  \033[31m✖ FAIL:\033[0m {$testName}" . ($details ? " - {$details}" : "") . "\n";
            echo $msg;
            self::$errors[] = $msg;
        }
    }

    public static function section(string $title): void {
        echo "\n\033[1;36m▶ {$title}\033[0m\n";
    }

    public static function report(): int {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "TEST RESULTS:\n";
        echo "  Total:  " . (self::$passed + self::$failed) . "\n";
        echo "  \033[32mPassed: " . self::$passed . "\033[0m\n";
        if (self::$failed > 0) {
            echo "  \033[31mFailed: " . self::$failed . "\033[0m\n";
            echo "\nFailed Assertions:\n";
            foreach (self::$errors as $err) {
                echo $err;
            }
            return 1;
        } else {
            echo "  \033[1;32mALL TESTS PASSED SUCCESSFULLY! 🚀\033[0m\n";
            return 0;
        }
    }
}

echo "\033[1;33m============================================================\033[0m\n";
echo "\033[1;33m       SVG DRY FRUITS - AUTOMATED VERIFICATION SUITE       \033[0m\n";
echo "\033[1;33m============================================================\033[0m\n";

// Run Individual Test Files
require_once __DIR__ . '/SecurityTest.php';
require_once __DIR__ . '/CartAndPricingTest.php';
require_once __DIR__ . '/OrderAndInventoryTest.php';

exit(TestSuite::report());
