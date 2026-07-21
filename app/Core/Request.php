<?php
namespace App\Core;

class Request {
    private array $body = [];
    private array $queryParams = [];

    public function __construct() {
        $this->parseRequest();
    }

    private function parseRequest(): void {
        // Explicitly isolate GET parameters from POST body payloads
        $this->queryParams = $this->sanitizeInput($_GET);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->body = $this->sanitizeInput($_POST);
        } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->body = $this->queryParams;
        }
    }

    /**
     * Recursively sanitizes array data mappings without dropping dynamic fields
     */
    private function sanitizeInput(array $data): array {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeInput($value);
            } else {
                $sanitized[$key] = trim(htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'));
            }
        }
        return $sanitized;
    }

    /**
     * Retrieves a sanitized parameter from the URL Query String (?id=value)
     */
    public function getParam(string $key, $default = null) {
        return $this->queryParams[$key] ?? $default;
    }

    public function getBody(): array {
        return $this->body;
    }

    /**
     * Detects if the current request payload was dispatched via an asynchronous AJAX vector stream
     */
    public function isAjax(): bool {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }
        if (!empty($_SERVER['HTTP_ACCEPT']) && str_contains(strtolower($_SERVER['HTTP_ACCEPT']), 'application/json')) {
            return true;
        }
        return false;
    }
}