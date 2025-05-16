<?php

namespace MindMap;

use Helpers\ResponseHelper;

class Router
{
    private array $routes = [];

    public function register(string $method, string $path, callable $handler): void
    {
        $this->routes[strtoupper($method)][$path] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        if (isset($this->routes[$method][$path])) {
            call_user_func($this->routes[$method][$path]);
        } else {
            ResponseHelper::json(['error' => 'Endpoint not found'], 400);

        }
    }
}
