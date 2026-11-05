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
        // If script is in /api subfolder, project root is parent
        $baseDir = preg_replace('#/api$#', '', $baseDir);
        if ($baseDir === '/' || $baseDir === '\\') {
            $baseDir = '';
        }
        return ($baseDir !== '' ? $baseDir : '') . '/' . $path;
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

    public static function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verifyCsrf(string $token): bool {
        return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function requireCsrf(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
            if (!self::verifyCsrf((string)$token)) {
                http_response_code(403);
                $isJson = (
                    (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                    (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
                );
                if ($isJson) {
                    self::json(['success' => false, 'error' => 'Security token expired or invalid. Please refresh the page.'], 403);
                }
                self::setFlash('error', 'Security validation failed (CSRF token expired). Please try again.');
                $referer = $_SERVER['HTTP_REFERER'] ?? self::url('admin');
                self::redirect($referer);
            }
        }
    }

    /**
     * Session-based sliding rate limiter
     */
    public static function checkRateLimit(string $actionKey, int $maxAttempts = 5, int $decaySeconds = 300): bool {
        $now = time();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $key = 'rate_' . md5($actionKey . '_' . $ip);

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }

        // Filter out attempts older than decay window
        $_SESSION[$key] = array_filter($_SESSION[$key], function($ts) use ($now, $decaySeconds) {
            return ($now - $ts) < $decaySeconds;
        });

        if (count($_SESSION[$key]) >= $maxAttempts) {
            return false; // Rate limit exceeded
        }

        $_SESSION[$key][] = $now;
        return true;
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

    public static function handleUpload(?array $file, string $prefix = 'img_'): ?string {
        if (!$file || empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // Limit size: 10MB
        if ($file['size'] > 10 * 1024 * 1024) {
            return null;
        }

        // 1. Strict extension validation
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext === 'jpeg') $ext = 'jpg';
        $allowedExts = ['jpg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $allowedExts, true)) {
            return null;
        }

        // 2. Strict MIME type verification via magic bytes
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            $allowedMimes = [
                'jpg'  => 'image/jpeg',
                'png'  => 'image/png',
                'webp' => 'image/webp',
                'gif'  => 'image/gif',
            ];
            if (!isset($allowedMimes[$ext]) || $allowedMimes[$ext] !== $mime) {
                return null;
            }
        }

        $cleanPrefix = preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix) ?: 'img_';
        $filename = $cleanPrefix . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

        $targetDirs = [
            ROOT_PATH . '/assets/uploads',
            ROOT_PATH . '/public/assets/uploads',
        ];

        $savedAny = false;
        $firstSaved = '';
        foreach ($targetDirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            if (is_dir($dir)) {
                $dest = $dir . '/' . $filename;
                if (!$savedAny) {
                    if (@move_uploaded_file($file['tmp_name'], $dest) || (php_sapi_name() === 'cli' && @copy($file['tmp_name'], $dest))) {
                        @chmod($dest, 0644);
                        $savedAny = true;
                        $firstSaved = $dest;
                    }
                } elseif ($firstSaved && file_exists($firstSaved)) {
                    @copy($firstSaved, $dest);
                    @chmod($dest, 0644);
                }
            }
        }

        return $savedAny ? '/assets/uploads/' . $filename : null;
    }
}

