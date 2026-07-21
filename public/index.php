<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Build request context early so session and URL config can adapt to localhost/subfolder setups.
$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
);
$scheme = $isHttps ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$scriptDir = str_replace('\\', '/', dirname($scriptName));
$basePath = ($scriptDir === '/' || $scriptDir === '.') ? '' : rtrim($scriptDir, '/');
$derivedAppUrl = $scheme . '://' . $host . $basePath;

// Use host-only cookie scope for localhost/IPs; use explicit domain for real hostnames.
$isLocalHost = in_array(strtolower(explode(':', $host)[0]), ['localhost', '127.0.0.1', '::1'], true)
    || (bool)preg_match('/^\d{1,3}(?:\.\d{1,3}){3}$/', explode(':', $host)[0]);
$cookieDomain = $isLocalHost ? '' : explode(':', $host)[0];


// Initialize secure session management before headers are sent
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_use_only_cookies', '1');
    ini_set('session.cookie_secure', $isHttps ? '1' : '0');
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $cookieDomain,
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Autoload components
require_once __DIR__ . '/../vendor/autoload.php';

// Simple environment configuration parser
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"");
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Ensure APP_URL exists even when .env is missing/misconfigured (common on local Apache).
if (empty($_ENV['APP_URL'])) {
    $_ENV['APP_URL'] = $derivedAppUrl;
    $_SERVER['APP_URL'] = $derivedAppUrl;
    putenv('APP_URL=' . $derivedAppUrl);
}

define('APP_VERSION', 'v1.5.0');

// Routing initialization
$router = new \App\Core\Router();
require_once __DIR__ . '/../routes/web.php';

// Check for the query string parameter first
$url = $_GET['url'] ?? '';

// Fallback: If Apache dropped the query parameter during internal directory rewriting,
// extract the clean relative path directly from the request URI.
if (empty($url)) {
    $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Remove this app's base path (e.g. /hrmis/public) so router sees only route fragments.
    if (!empty($basePath) && strpos($url, $basePath) === 0) {
        $url = substr($url, strlen($basePath));
    }

    // Also support setups where requests arrive prefixed with /public after rewrites.
    if (strpos($url, '/public/') === 0) {
        $url = substr($url, 7);
    }

    // Final sanitize for router matching.
    $url = trim($url, '/');
}

$router->dispatch($url);