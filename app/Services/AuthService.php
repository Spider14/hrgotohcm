<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\User;

class AuthService {
    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function authenticate(string $identifier, string $password, string $ipAddress): array {
        // 1. Validate identifier format
        if ($identifier === '') {
            return ['success' => false, 'message' => 'Username or email is required.'];
        }

        // 2. Brute Force Verification Engine
        $failedAttempts = $this->userModel->getRecentFailedAttemptsCount($ipAddress, $identifier, 10);
        if ($failedAttempts >= 3) {
            return ['success' => false, 'message' => 'Account is temporarily locked due to 3 failed attempts. Try again in 10 minutes.'];
        }

        // 3. Evaluate Credentials
        $user = $this->userModel->findByIdentifier($identifier);
        if (!$user) {
            $this->userModel->registerLoginAttempt($ipAddress, $identifier, false);
            return ['success' => false, 'message' => 'Invalid credentials provided.'];
        }

        if ($user['status'] !== 'Active') {
            return ['success' => false, 'message' => 'Account state restricted. Contact HR Administrator.'];
        }

        if (!password_verify($password, $user['password'])) {
            $this->userModel->registerLoginAttempt($ipAddress, $identifier, false);
            return ['success' => false, 'message' => 'Invalid credentials provided.'];
        }

        // 4. Handle Authenticated Context
        $this->userModel->registerLoginAttempt($ipAddress, $identifier, true);
        $this->userModel->updateLoginTimestamp((int)$user['id']);
        
        // Session Configuration mapping
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_fullname'] = $user['fullname'];
        $_SESSION['user_role'] = $user['role_name'];
        $_SESSION['last_activity'] = time();
        
        session_regenerate_id(true); // Mutate identifier tracking

        $this->userModel->logAuditTrail((int)$user['id'], 'AUTH_LOGIN', 'User logged in successfully.');

        $redirectUrl = '/dashboard';
        if (strcasecmp((string)($user['role_name'] ?? ''), 'Staff') === 0) {
            $redirectUrl = '/staff/portal';
        }

        return [
            'success' => true,
            'message' => 'Authentication success.',
            'redirect' => $redirectUrl,
        ];
    }
}