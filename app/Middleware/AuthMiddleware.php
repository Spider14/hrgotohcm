<?php
declare(strict_types=1);

namespace App\Middleware;

class AuthMiddleware {
    /**
     * Protect internal routes against unauthorized anonymous execution.
     */
    public static function handle(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/login');
            exit;
        }

        // Enforce maximum absolute idle timeout duration (30 Minutes)
        $maxIdleTime = 1800; 
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $maxIdleTime)) {
            $_SESSION = [];
            session_destroy();
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/login?reason=timeout');
            exit;
        }
        $_SESSION['last_activity'] = time();
    }

    /**
     * Strict RBAC gate matching authenticated contexts against matrix allocations.
     */
    public static function checkRole(array $allowedRoles): void {
        self::handle();
        $userRole = $_SESSION['user_role'] ?? 'Staff';
        if (!in_array($userRole, $allowedRoles, true)) {
            http_response_code(403);
            echo "403 Unauthorized Access - Your role permissions lack visibility authorization privileges for this node.";
            exit;
        }
    }
}