<?php

namespace MindMap;

use Connectivity\DB;
use App\Helpers\ResponseHelper;

class Router
{
    private array $routes = [];

    public function register(string $method, string $path, array $handler): void
    {
        $this->routes[strtoupper($method)][$path] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);

        if (!isset($this->routes[$method][$path])) {
            ResponseHelper::json(['error' => 'Endpoint not found'], 404);
            return;
        }

        $handler = $this->routes[$method][$path];

        if (is_array($handler) && is_string($handler[0]) && class_exists($handler[0])) {
            $className = $handler[0];
            $methodName = $handler[1];

            $instance = new $className(new DB());
            call_user_func([$instance, $methodName]);
            return;
        }
        if (is_callable($handler)) {
            call_user_func($handler);
            return;
        }
        ResponseHelper::json(['error' => 'Invalid route handler'], 500);
    }
}
