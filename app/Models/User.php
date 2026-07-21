<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

class User {
    private PDO $db;

    public function __classConstruction() {
        // Fallback wrapper construct pattern
    }

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Locate a user record by email and attach role metadata.
     */
    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare(
            "SELECT u.*, r.role_name
             FROM users u
             JOIN roles r ON u.role_id = r.id
             WHERE u.email = :email
               AND u.deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Locate a user by either username or email for flexible login input.
     */
    public function findByIdentifier(string $identifier): ?array {
        $stmt = $this->db->prepare(
            "SELECT u.*, r.role_name
             FROM users u
             JOIN roles r ON u.role_id = r.id
             WHERE (u.email = :email_identifier OR u.username = :username_identifier)
               AND u.deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([
            'email_identifier' => $identifier,
            'username_identifier' => $identifier,
        ]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function updateLoginTimestamp(int $id): void {
        try {
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return;
        } catch (Throwable $e) {
            // Fall through to alternate column used in migration schema.
        }

        try {
            $stmt = $this->db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = :id");
            $stmt->execute(['id' => $id]);
        } catch (Throwable $e) {
            // Non-blocking.
        }
    }

    public function registerLoginAttempt(string $ip, string $identifier, bool $isSuccessful): void {
        try {
            $stmt = $this->db->prepare("INSERT INTO login_attempts (ip_address, email, is_successful) VALUES (:ip, :email, :status)");
            $stmt->execute([
                'ip' => $ip,
                'email' => $identifier,
                'status' => $isSuccessful ? 1 : 0,
            ]);
        } catch (Throwable $e) {
            // Non-blocking.
        }
    }

    public function getRecentFailedAttemptsCount(string $ip, string $identifier, int $minutes = 10): int {
        try {
            $minutes = max(1, (int)$minutes);
            $stmt = $this->db->prepare(
                "SELECT COUNT(*)
                 FROM login_attempts
                 WHERE ip_address = :ip
                   AND email = :email
                   AND is_successful = 0
                   AND attempted_at >= DATE_SUB(NOW(), INTERVAL {$minutes} MINUTE)"
            );
            $stmt->execute([
                'ip' => $ip,
                'email' => $identifier,
            ]);
            return (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            // Missing table/column should not break authentication.
            return 0;
        }
    }

    public function logAuditTrail(int $userId, string $action, string $description): void {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO audit_logs (user_id, action, description, ip_address, user_agent)
                 VALUES (:user_id, :action, :description, :ip, :ua)"
            );
            $stmt->execute([
                'user_id' => $userId,
                'action' => $action,
                'description' => $description,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        } catch (Throwable $e) {
            // Non-blocking.
        }
    }
}
