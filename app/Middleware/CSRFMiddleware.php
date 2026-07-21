<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Helpers\Security;

class CSRFMiddleware {
    public static function validate(Request $request): void {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if ($token === null) {
            $body = $request->getBody();
            $token = $body['csrf_token'] ?? null;
        }

        if (Security::verifyCsrfToken(is_string($token) ? $token : null)) {
            return;
        }

        http_response_code(403);

        if ($request->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'CSRF token validation failed.'
            ]);
        } else {
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'CSRF token validation failed.';
        }

        exit;
    }
}
