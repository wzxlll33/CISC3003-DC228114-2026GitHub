<?php

namespace App\Core;

use Closure;
use Throwable;

class Router
{
    protected array $routes = [];

    protected string $prefix = '';

    protected App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function get(string $path, string|Closure $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, string|Closure $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function group(string $prefix, callable $callback): void
    {
        $previousPrefix = $this->prefix;
        $this->prefix = $this->normalizePath($previousPrefix . '/' . trim($prefix, '/'));
        $callback($this);
        $this->prefix = $previousPrefix;
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $routeMethod = $method === 'HEAD' ? 'GET' : $method;
        $uri = $this->normalizePath($uri);

        foreach ($this->routes[$routeMethod] ?? [] as $route) {
            if (preg_match($route['pattern'], $uri, $matches) !== 1) {
                continue;
            }

            $params = [];

            foreach ($route['parameters'] as $parameter) {
                if (isset($matches[$parameter])) {
                    $params[$parameter] = $matches[$parameter];
                }
            }

            $this->executeHandler($route['handler'], $params);
            return;
        }

        $this->app->response()->html('<h1>404 Not Found</h1>', 404);
    }

    protected function addRoute(string $method, string $path, string|Closure $handler): void
    {
        $fullPath = $this->normalizePath($this->prefix . '/' . trim($path, '/'));
        $parameters = [];
        $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', static function (array $matches) use (&$parameters): string {
            $parameters[] = $matches[1];
            return '(?P<' . $matches[1] . '>[^/]+)';
        }, $fullPath);

        $this->routes[$method][] = [
            'pattern' => '#^' . $pattern . '$#',
            'parameters' => $parameters,
            'handler' => $handler,
        ];
    }

    protected function executeHandler(string|Closure $handler, array $params): void
    {
        if ($handler instanceof Closure) {
            $handler($this->app, $params);
            return;
        }

        [$controllerClass, $method] = array_pad(explode('@', $handler, 2), 2, null);

        if ($controllerClass === null || $method === null) {
            throw new \RuntimeException('Invalid route handler: ' . $handler);
        }

        if (!class_exists($controllerClass)) {
            throw new \RuntimeException('Controller not found: ' . $controllerClass);
        }

        $controller = new $controllerClass($this->app);

        if (!method_exists($controller, $method)) {
            throw new \RuntimeException('Controller method not found: ' . $handler);
        }

        $controller->{$method}(...array_values($params));
    }

    protected function normalizePath(string $path): string
    {
        $normalized = '/' . trim($path, '/');
        return $normalized === '/' ? '/' : rtrim($normalized, '/');
    }
}
