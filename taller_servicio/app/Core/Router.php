<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class Router
{
    private array $routes = [];
    private ?string $currentGroupPrefix = null;
    private array $currentGroupMiddleware = [];
    private $notFoundHandler = null;

    public function get(string $path, array $handler): self
    {
        $this->addRoute('GET', $path, $handler);
        return $this;
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable|array $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, array $handler): self
    {
        $this->addRoute('DELETE', $path, $handler);
        return $this;
    }

    public function middleware(array $middleware): self
    {
        $this->routes[array_key_last($this->routes)]['middleware'] = $middleware;
        return $this;
    }

    public function group(array $options, callable $callback): void
    {
        $this->currentGroupPrefix = $options['prefix'] ?? null;
        $this->currentGroupMiddleware = $options['middleware'] ?? [];

        $callback($this);

        $this->currentGroupPrefix = null;
        $this->currentGroupMiddleware = [];
    }

    public function setNotFoundHandler(callable|array $handler): void
    {
        $this->notFoundHandler = $handler;
    }

    private function addRoute(string $method, string $path, array $handler): void
    {
        $uri = ($this->currentGroupPrefix ? rtrim($this->currentGroupPrefix, '/') : '') . '/' . ltrim($path, '/');
        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'handler' => $handler,
            'middleware' => $this->currentGroupMiddleware,
        ];
    }

    public function dispatch(string $uri, string $method): void
{
    $requestUri = rtrim(parse_url($uri, PHP_URL_PATH) ?? '/', '/') ?: '/';
    $requestMethod = strtoupper($method);

    foreach ($this->routes as $route) {
        // Ruta normalizada (sin slash final excepto '/')
        $routeUri = rtrim($route['uri'], '/') ?: '/';

        // Patrón con grupos nombrados y segmentos seguros
        $pattern = preg_replace('/{(\w+)}/', '(?P<$1>[^/]+)', $routeUri);

        if ($route['method'] === $requestMethod && preg_match("#^{$pattern}$#", $requestUri, $matches)) {
            // Middleware
            foreach ($route['middleware'] as $middleware) {
                [$class, $func] = $middleware;
                (new $class())->$func();
            }

            // Handler
            [$controller, $action] = $route['handler'];

            // Ordenar parámetros según aparición en la ruta
            preg_match_all('/{(\w+)}/', $routeUri, $paramNames);
            $orderedParams = [];
            foreach ($paramNames[1] as $name) {
                if (array_key_exists($name, $matches)) {
                    $v = $matches[$name];
                    $orderedParams[] = ctype_digit($v) ? (int)$v : $v;
                }
            }

            // Invocación POSICIONAL (evita argumentos nombrados)
            (new $controller())->$action(...$orderedParams);
            return;
        }
    }

    http_response_code(404);
    if ($this->notFoundHandler !== null) {
        $this->invokeHandler($this->notFoundHandler);
        return;
    }
    $this->invokeHandler(null);
}

    private function invokeHandler(callable|array $handler): void
    {
        if ($handler === null) {
            echo '404 - Página no encontrada';
            return;
        }

        if ($handler instanceof Closure) {
            $handler();
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;

            try {
                $reflection = new ReflectionClass($class);
                $instance = $reflection->newInstance();

                if (!$reflection->hasMethod($method)) {
                    throw new ReflectionException("Método {$method} no encontrado en {$class}");
                }

                $reflectionMethod = new ReflectionMethod($class, $method);
                if (!$reflectionMethod->isPublic()) {
                    throw new ReflectionException("Método {$method} de {$class} no es accesible");
                }

                $reflectionMethod->invoke($instance);
                return;
            } catch (ReflectionException $exception) {
                http_response_code(500);
                echo 'Error al despachar la ruta: ' . $exception->getMessage();
                return;
            }
        }

        if (is_callable($handler)) {
            call_user_func($handler);
            return;
        }

        http_response_code(500);
        echo 'Handler de ruta inválido';
    }
}
