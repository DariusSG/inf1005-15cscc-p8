<?php

namespace App\Core;

use App\Core\Request;
use App\Core\Response;
use App\Core\Container;

class Router
{
    private $routes = [];
    private $prefix = '';
    private $globalMiddleware = [];

    // -----------------------------
    // Route group prefix
    // -----------------------------
    public function prefix(string $prefix, callable $callback)
    {
        $previousPrefix = $this->prefix;
        $this->prefix .= $prefix;
        $callback($this);
        $this->prefix = $previousPrefix;
    }

    // -----------------------------
    // Route group middleware
    // -----------------------------
    public function middleware(array $middleware, callable $callback)
    {
        $previous = $this->globalMiddleware;
        $this->globalMiddleware = array_merge($this->globalMiddleware, $middleware);
        $callback($this);
        $this->globalMiddleware = $previous;
    }

    public function get(string $path, string $handler, array $middleware = [])
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, string $handler, array $middleware = [])
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    // -----------------------------
    // Add route with middleware
    // -----------------------------
    private function addRoute(string $method, string $path, string $handler, array $middleware = [])
    {
        $path = $this->prefix . $path;
        $this->routes[$method][$path] = [
            'handler' => $handler,
            'middleware' => array_merge($this->globalMiddleware, $middleware)
        ];
    }

    // -----------------------------
    // Resolve current request
    // -----------------------------
    public function resolve()
    {
        $method = Request::method();
        $uri = Request::uri();

        foreach ($this->routes[$method] ?? [] as $route => $data) {

            // Capture parameter names: {id}, {user_id}, {slug123}, etc.
            preg_match_all('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', $route, $paramNames);
            $paramNames = $paramNames[1]; // array of parameter names

            // Convert route to regex pattern
            $pattern = "#^" . preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([^/]+)', $route) . "$#";

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // remove full match

                // Map matches to parameter names
                $params = [];
                foreach ($paramNames as $i => $name) {
                    $params[$name] = $matches[$i] ?? null;
                }

                // -----------------------------
                // Run middleware
                // -----------------------------
                foreach ($data['middleware'] as $mw) {
                    $class = "App\\Middleware\\$mw";
                    if (class_exists($class)) {
                        try {
                            // Try DI container first
                            $instance = Container::resolve($mw);
                            if (method_exists($instance, 'handle')) {
                                $instance->handle();
                            } else {
                                $class::handle(); // fallback to static
                            }
                        } catch (\Exception $e) {
                            // Fallback to static if container resolution fails
                            $class::handle();
                        }
                    }
                }

                // -----------------------------
                // Call controller method
                // -----------------------------
                [$controller, $methodName] = explode('@', $data['handler']);
                $controller = "App\\Controllers\\" . $controller;

                return (new $controller)->$methodName(...array_values($params));
            }
        }

        // No matching route
        Response::json(["error" => "Route not found"], 404);
    }
}