<?php
/**
 * SVG Dry Fruits - Helper Functions
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

class Helpers {
    public static function formatPaise(int $paise): string {
        $rupees = $paise / 100;
        return '₹' . number_format($rupees, ($paise % 100 === 0) ? 0 : 2);
    }

    public static function paiseToRupees(int $paise): float {
        return round($paise / 100, 2);
    }

    public static function sanitize(string $str): string {
        return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
    }

    public static function url(string $path = ''): string {
        $path = ltrim($path, '/');
        // Check if we are running in a subfolder or root
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseDir = rtrim(dirname($scriptName), '/\\');
        if ($baseDir === '/' || $baseDir === '\\') {
            $baseDir = '';
        }
        return $baseDir . '/' . $path;
    }

    public static function asset(string $path): string {
        $clean = preg_replace('#^/?assets/#', '', trim($path));
        return self::url('assets/' . $clean);
    }


    public static function redirect(string $url): void {
        header("Location: " . $url);
        exit;
    }

    public static function json(array $data, int $statusCode = 200): void {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function setFlash(string $type, string $message): void {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function getFlashes(): array {
        $flashes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flashes;
    }

    public static function csrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(string $token): bool {
        return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function render(string $view, array $data = []): void {
        extract($data);
        $viewFile = VIEWS_PATH . '/' . ltrim($view, '/') . '.php';
        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo "<h1>404 - View Not Found: " . htmlspecialchars($view) . "</h1>";
            exit;
        }
        require $viewFile;
    }
}
