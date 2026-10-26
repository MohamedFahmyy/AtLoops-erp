<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

class Router
{
    private array $routes = [];
    private array $groupStack = [];

    public function get(string $uri, mixed $action, array $middleware = []): self
    {
        return $this->addRoute('GET', $uri, $action, $middleware);
    }

    public function post(string $uri, mixed $action, array $middleware = []): self
    {
        return $this->addRoute('POST', $uri, $action, $middleware);
    }

    public function put(string $uri, mixed $action, array $middleware = []): self
    {
        return $this->addRoute('PUT', $uri, $action, $middleware);
    }

    public function delete(string $uri, mixed $action, array $middleware = []): self
    {
        return $this->addRoute('DELETE', $uri, $action, $middleware);
    }

    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function addRoute(string $method, string $uri, mixed $action, array $middleware = []): self
    {
        $prefix = '';
        $groupMiddleware = [];

        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
            if (isset($group['middleware'])) {
                $m = is_array($group['middleware']) ? $group['middleware'] : [$group['middleware']];
                $groupMiddleware = array_merge($groupMiddleware, $m);
            }
        }

        $fullUri = rtrim($prefix . '/' . trim($uri, '/'), '/');
        if ($fullUri === '') {
            $fullUri = '/';
        }

        $allMiddleware = array_merge($groupMiddleware, $middleware);

        $this->routes[] = [
            'method' => strtoupper($method),
            'uri' => $fullUri,
            'action' => $action,
            'middleware' => $allMiddleware,
            'pattern' => $this->compilePattern($fullUri),
        ];

        return $this;
    }

    private function compilePattern(string $uri): string
    {
        // Convert {param} or {param:regex} into regex capture groups
        $pattern = preg_replace_callback('/\{([a-zA-Z0-9_]+)(?::([^\}]+))?\}/', function ($matches) {
            $param = $matches[1];
            $regex = $matches[2] ?? '[^/]+';
            return "(?P<{$param}>{$regex})";
        }, $uri);

        return '#^' . $pattern . '$#u';
    }

    public function dispatch(string $method, string $uri): mixed
    {
        $method = strtoupper($method);
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        // Handle method override for forms (e.g., _method=PUT or _method=DELETE)
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper((string)$_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $override;
            }
        }

        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        // Strip project base path if running in subdirectory (e.g. /atloops-os/public)
        $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptName !== '/' && $scriptName !== '.' && $scriptName !== '' && str_starts_with($path, $scriptName)) {
            $path = substr($path, strlen($scriptName));
        }

        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Run route middleware pipeline
                foreach ($route['middleware'] as $middleware) {
                    $this->runMiddleware($middleware);
                }

                return $this->runAction($route['action'], $params);
            }
        }

        // Route not found
        http_response_code(404);
        if ($this->isAjax()) {
            Response::json(['error' => 'Route not found: ' . $path], 404);
        }

        $errorView = dirname(__DIR__) . '/Views/errors/404.php';
        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo "<h1>404 Not Found</h1><p>The requested URL was not found on this server.</p>";
        }
        exit;
    }

    private function runMiddleware(mixed $middleware): void
    {
        if (is_string($middleware)) {
            $instance = new $middleware();
            if (method_exists($instance, 'handle')) {
                $instance->handle();
            }
        } elseif (is_callable($middleware)) {
            $middleware();
        } elseif (is_object($middleware) && method_exists($middleware, 'handle')) {
            $middleware->handle();
        }
    }

    private function runAction(mixed $action, array $params): mixed
    {
        if (is_callable($action)) {
            return call_user_func_array($action, $params);
        }

        if (is_array($action)) {
            [$controllerClass, $method] = $action;
            $controller = new $controllerClass();
            return call_user_func_array([$controller, $method], $params);
        }

        if (is_string($action) && str_contains($action, '@')) {
            [$class, $method] = explode('@', $action);
            $fullClass = str_starts_with($class, 'App\\') ? $class : 'App\\Controllers\\' . $class;
            $controller = new $fullClass();
            return call_user_func_array([$controller, $method], $params);
        }

        throw new RuntimeException("Invalid route action specified.");
    }

    private function isAjax(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'));
    }
}
