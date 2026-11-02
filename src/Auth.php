<?php
/**
 * SVG Dry Fruits - Authentication Manager
 * Secure session handling for the Admin Dashboard.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Helpers.php';

class Auth {
    public static function check(): bool {
        return !empty($_SESSION['admin_user_id']);
    }

    public static function admin(): ?array {
        if (!self::check()) {
            return null;
        }
        return [
            'id' => $_SESSION['admin_user_id'],
            'email' => $_SESSION['admin_email'] ?? '',
        ];
    }

    public static function login(string $email, string $password): bool {
        self::bootstrapAdmin();

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, email, password_hash FROM admins WHERE LOWER(email) = LOWER(?) LIMIT 1");
        $stmt->execute([trim($email)]);
        $admin = $stmt->fetch();

        if (!$admin) {
            return false;
        }

        if (password_verify($password, $admin['password_hash'])) {
            // Re-hash if needed
            if (password_needs_rehash($admin['password_hash'], PASSWORD_BCRYPT)) {
                $newHash = password_hash($password, PASSWORD_BCRYPT);
                $up = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
                $up->execute([$newHash, $admin['id']]);
            }

            session_regenerate_id(true);
            $_SESSION['admin_user_id'] = $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];
            return true;
        }

        return false;
    }

    public static function logout(): void {
        unset($_SESSION['admin_user_id'], $_SESSION['admin_email']);
        session_regenerate_id(true);
    }

    public static function requireAuth(): void {
        if (!self::check()) {
            Helpers::redirect(Helpers::url('admin/login'));
        }
    }

    public static function bootstrapAdmin(): void {
        try {
            $pdo = Database::getConnection();
            $email = strtolower(trim(ADMIN_EMAIL));
            $pass = trim(ADMIN_PASSWORD);

            $stmt = $pdo->prepare("SELECT id, password_hash FROM admins WHERE LOWER(email) = LOWER(?) LIMIT 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if (!$admin) {
                $id = 'e0000001-0001-4000-8000-000000000001';
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $ins = $pdo->prepare("INSERT INTO admins (id, email, password_hash) VALUES (?, ?, ?)");
                $ins->execute([$id, $email, $hash]);
            } elseif (!password_verify($pass, $admin['password_hash'])) {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $up = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
                $up->execute([$hash, $admin['id']]);
            }
        } catch (Throwable $e) {
            // Ignore if DB not yet initialized
        }
    }
}
