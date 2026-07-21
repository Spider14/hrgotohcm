<?php
declare(strict_types=1);

namespace App\Helpers;

class Security {
    
    /**
     * Generate a cryptographically secure CSRF token and store it in the session.
     */
    public static function generateCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify if a provided CSRF token matches the one stored in the session.
     */
    public static function verifyCsrfToken(?string $token): bool {
        if (!isset($_SESSION['csrf_token']) || $token === null) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Escape strings safely for HTML template rendering outputs.
     * Updated to accept nullable values (?string) to handle optional database entries.
     */
    public static function escape(?string $value): string {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate generic email format.
     */
    public static function validateBtuEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function setFlash(string $type, string $message): void {
        $_SESSION['_flash'][$type] = $message;
    }

    public static function getFlash(?string $type = null): string|array {
        if ($type === null) {
            $flashes = $_SESSION['_flash'] ?? [];
            unset($_SESSION['_flash']);
            return $flashes;
        }
        $msg = $_SESSION['_flash'][$type] ?? '';
        unset($_SESSION['_flash'][$type]);
        return $msg;
    }

    public static function hasFlash(string $type): bool {
        return isset($_SESSION['_flash'][$type]);
    }
}