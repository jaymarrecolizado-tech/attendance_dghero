<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /**
     * @var array<string,array<string,mixed>>
     */
    private array $routes;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function dispatch(string $routeName, string $method): void
    {
        $method = strtoupper($method);
        $route = $this->routes[$routeName] ?? null;
        if (!$route) {
            http_response_code(404);
            echo 'Not Found';
            return;
        }

        $handler = $route[$method] ?? null;
        if (!$handler) {
            $fallback = $route['_fallback'] ?? null;
            if (is_callable($fallback)) {
                $fallback();
                return;
            }
            http_response_code(405);
            echo 'Method Not Allowed';
            return;
        }

        if (!$this->runGuards($route, $method)) {
            return;
        }

        $this->invokeHandler($handler);
    }

    private function runGuards(array $route, string $method): bool
    {
        if (empty($route['_guards']) || !is_array($route['_guards'])) {
            return true;
        }

        foreach ($route['_guards'] as $guard) {
            if ($guard === 'admin' && empty($_SESSION['admin_id'])) {
                if ($method === 'GET') {
                    header('Location: ?r=admin_login');
                } else {
                    http_response_code(403);
                    echo 'Forbidden';
                }
                return false;
            }
            if ($guard === 'staff' && empty($_SESSION['staff'])) {
                http_response_code(403);
                echo 'Forbidden';
                return false;
            }
        }

        return true;
    }

    /**
     * @param callable|array<int,string|object> $handler
     */
    private function invokeHandler($handler): void
    {
        if (is_array($handler)) {
            $target = $handler[0] ?? null;
            $method = $handler[1] ?? null;
            if (is_string($target)) {
                $instance = new $target();
                $callable = [$instance, $method];
            } else {
                $callable = [$target, $method];
            }
            call_user_func($callable);
            return;
        }

        if (is_callable($handler)) {
            $handler();
            return;
        }

        throw new \RuntimeException('Invalid route handler');
    }
}

