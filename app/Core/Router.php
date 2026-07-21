<?php
declare(strict_types=1);

namespace App\Core;

class Router {
    private array $routes = [];

    public function add(string $method, string $route, string $controllerAction): void {
        $this->routes[] = [
            'method' => strtoupper($method),
            'route' => trim($route, '/'),
            'action' => $controllerAction
        ];
    }

    public function dispatch(string $url): void {
        // 1. Remove any trailing/leading slashes
        $url = trim($url, '/');
        
        // 2. Strip off query strings if they are appended to the raw dispatch URI (e.g., onboard/submit?abc=123)
        if (($pos = strpos($url, '?')) !== false) {
            $url = substr($url, 0, $pos);
        }

        // 3. Fallback check: If the URL got prefixed with public/ due to server setup, strip it
        if (strpos($url, 'public/') === 0) {
            $url = substr($url, 7);
        }
        
        $method = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route) {
            if ($route['route'] === $url && $route['method'] === $method) {
                list($controllerClass, $action) = explode('@', $route['action']);
                $controllerClass = "App\\Controllers\\" . $controllerClass;
                
                if (class_exists($controllerClass)) {
                    $controllerInstance = new $controllerClass();
                    if (method_exists($controllerInstance, $action)) {
                        $controllerInstance->$action(new Request());
                        return;
                    }
                }
            }
        }
		
		// Fallback: 404 Route handling
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        echo "404 - Page Not Found. Router could not map target URL path: [{$url}] with method [{$method}]";
    }
}