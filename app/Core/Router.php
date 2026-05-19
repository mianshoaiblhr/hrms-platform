<?php
// ============================================================
// app/Core/Router.php - MVC Router
// ============================================================

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private string $prefix = '';
    private array $groupMiddlewares = [];

    // --------------------------------------------------------
    // ROUTE REGISTRATION
    // --------------------------------------------------------

    public function get(string $path, string|callable $handler, array $middlewares = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, string|callable $handler, array $middlewares = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, string|callable $handler, array $middlewares = []): self
    {
        return $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    public function delete(string $path, string|callable $handler, array $middlewares = []): self
    {
        return $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    public function group(array $options, callable $callback): void
    {
        $previousPrefix     = $this->prefix;
        $previousMiddlewares = $this->groupMiddlewares;

        $this->prefix .= $options['prefix'] ?? '';
        $this->groupMiddlewares = array_merge(
            $this->groupMiddlewares,
            $options['middleware'] ?? []
        );

        $callback($this);

        $this->prefix          = $previousPrefix;
        $this->groupMiddlewares = $previousMiddlewares;
    }

    private function addRoute(string $method, string $path, $handler, array $middlewares): self
    {
        $fullPath = $this->prefix . $path;
        $allMiddlewares = array_merge($this->groupMiddlewares, $middlewares);

        $this->routes[] = [
            'method'      => $method,
            'path'        => $fullPath,
            'pattern'     => $this->pathToPattern($fullPath),
            'handler'     => $handler,
            'middlewares' => $allMiddlewares,
        ];

        return $this;
    }

    // --------------------------------------------------------
    // DISPATCH
    // --------------------------------------------------------

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri    = rtrim($uri, '/') ?: '/';

        // Handle method override (for HTML forms)
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = $this->extractParams($matches);

                // Run middlewares
                foreach ($route['middlewares'] as $middlewareClass) {
                    $middleware = new $middlewareClass();
                    $response = $middleware->handle();
                    if ($response === false) return;
                }

                // Dispatch handler
                $this->callHandler($route['handler'], $params);
                return;
            }
        }

        // 404
        http_response_code(404);
        include dirname(__DIR__, 2) . '/resources/views/errors/404.php';
    }

    private function callHandler($handler, array $params): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
            return;
        }

        if (is_string($handler)) {
            [$class, $method] = explode('@', $handler);
            $fullClass = "\\App\\Controllers\\{$class}";

            if (!class_exists($fullClass)) {
                throw new \RuntimeException("Controller {$fullClass} not found.");
            }

            $controller = new $fullClass();

            if (!method_exists($controller, $method)) {
                throw new \RuntimeException("Method {$method} not found in {$fullClass}.");
            }

            call_user_func_array([$controller, $method], $params);
            return;
        }

        throw new \RuntimeException('Invalid route handler.');
    }

    private function pathToPattern(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    private function extractParams(array $matches): array
    {
        return array_filter($matches, fn($key) => !is_int($key), ARRAY_FILTER_USE_KEY);
    }
}
