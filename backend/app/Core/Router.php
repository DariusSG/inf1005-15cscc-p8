<?php

namespace App\Core;

use App\Middleware\Middleware;
use Exception;
use RuntimeException;

class Router
{
    private array $routes = [];
    private string $prefix = '';
    private array $globalMiddleware = [];

    public function prefix(string $prefix, callable $callback): void
    {
        $previousPrefix = $this->prefix;
        $this->prefix .= $prefix;
        $callback($this);
        $this->prefix = $previousPrefix;
    }

    public function middleware(array $middleware, callable $callback): void
    {
        $previous = $this->globalMiddleware;
        $this->globalMiddleware = array_merge($this->globalMiddleware, $middleware);
        $callback($this);
        $this->globalMiddleware = $previous;
    }

    public function get(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function put(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function post(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function delete(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, string $handler, array $middleware = []): void
    {
        $path = $this->prefix . $path;

        preg_match_all('#\{([a-zA-Z_][a-zA-Z0-9_]*)}#', $path, $matches);
        $regex = "#^" . preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*}#', '([^/]+)', $path) . "$#";

        $this->routes[$method][$path] = [
            'handler'    => $handler,
            'middleware' => array_merge($this->globalMiddleware, $middleware),
            'pattern'    => $regex,
            'params'     => $matches[1] // Parameter names
        ];
    }

    /**
     * @throws Exception
     */
    public function resolve(): void
    {
        $method = Request::method();
        $uri    = Request::uri();

        foreach ($this->routes[$method] ?? [] as $routeData) {
            if (preg_match($routeData['pattern'], $uri, $matches)) {
                array_shift($matches); // Remove full match

                // Map matches to parameter names
                $params = array_combine($routeData['params'], $matches);

                // Execute Middleware Stack
                $this->runMiddleware($routeData['middleware']);

                // Execute Controller
                $this->dispatch($routeData['handler'], $params);
                return;
            }
        }

        Response::json(["error" => "Route not found"], 404);
    }

    private function runMiddleware(array $middlewares): void
    {
        foreach ($middlewares as $mw) {
            $parts = explode(':', $mw, 2);
            $fullClass = "App\\Middleware\\" . $parts[0];
            $args = isset($parts[1]) ? explode(',', $parts[1]) : [];

            if (!class_exists($fullClass) || !is_subclass_of($fullClass, Middleware::class)) {
                throw new RuntimeException("Middleware [$fullClass] is invalid.");
            }

            // Interface guarantees this static method exists
            $fullClass::handle(...$args);
        }
    }

    /**
     * @throws Exception
     */
    private function dispatch(string $handler, array $params): void
    {
        [$class, $method] = explode('@', $handler);
        $controllerName = "App\\Controllers\\" . $class;

        $controller = Container::resolve($controllerName);

        if (!method_exists($controller, $method)) {
            throw new RuntimeException("Method [$method] not found in [$controllerName].");
        }

        // Call the method with URI parameters
        $controller->$method(...array_values($params));
    }
}