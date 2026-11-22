<?php

namespace Flame;

class Router
{
    public $routes = [];

    public static function redirect($url)
    {
        if (
            !is_string($url)
            || $url === ''
            || strpos($url, '\\') !== false
            || preg_match('/[\r\n\x00-\x1f\x7f]/', $url)
            || substr($url, 0, 2) === '//'
        ) {
            throw new \InvalidArgumentException('Redirect target must be a safe local URL.');
        }

        $parts = parse_url($url);
        if ($parts === false || isset($parts['scheme']) || isset($parts['host']) || isset($parts['user'])) {
            throw new \InvalidArgumentException('Redirect target must be a safe local URL.');
        }

        header("Location: $url");
        exit();
    }

    public function addRoute($route, $controller, $action, $params = [], $methods = ['GET'])
    {
        $route = self::normalizePath($route);
        if (!is_string($controller) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $controller)) {
            throw new \InvalidArgumentException('Controller must be a class name without a namespace or path.');
        }
        if (!is_string($action) || !preg_match('/^(?!__)[A-Za-z_][A-Za-z0-9_]*$/D', $action)) {
            throw new \InvalidArgumentException('Action must be a valid method name.');
        }
        if (!is_array($params)) {
            throw new \InvalidArgumentException('Route parameters must be an array.');
        }
        if (isset($this->routes[$route])) {
            throw new \InvalidArgumentException("Route '{$route}' is already registered.");
        }

        $methods = is_string($methods) ? [$methods] : $methods;
        if (!is_array($methods) || $methods === []) {
            throw new \InvalidArgumentException('At least one HTTP method must be registered.');
        }
        $normalizedMethods = [];
        foreach ($methods as $method) {
            if (!is_string($method) || !preg_match('/^[A-Za-z]+$/D', $method)) {
                throw new \InvalidArgumentException('Invalid HTTP method in route definition.');
            }
            $normalizedMethods[] = strtoupper($method);
        }
        $normalizedMethods = array_values(array_unique($normalizedMethods));

        $this->routes[$route] = [
            'controller' => $controller,
            'action' => $action,
            'params' => $params,
            'methods' => $normalizedMethods,
        ];

        return $this;
    }

    public function match($path, $method = 'GET')
    {
        $path = self::normalizePath($path);
        $route = $this->routes[$path] ?? null;
        if ($route === null) {
            return null;
        }

        if (!in_array(strtoupper((string) $method), $route['methods'], true)) {
            return [
                'method_not_allowed' => true,
                'allow' => $route['methods'],
            ];
        }

        return $route;
    }

    public static function normalizePath($path)
    {
        if (
            !is_string($path)
            || $path === ''
            || $path[0] !== '/'
            || substr($path, 0, 2) === '//'
            || strpos($path, '\\') !== false
            || preg_match('/[\x00-\x20\x7f]/', $path)
            || preg_match('/%(?:2f|5c)/i', $path)
        ) {
            throw new \InvalidArgumentException('Invalid request path.');
        }

        if ($path === '/') {
            return '/';
        }

        $segments = explode('/', trim($path, '/'));
        $normalized = [];
        foreach ($segments as $segment) {
            $segment = rawurldecode($segment);
            if (
                $segment === ''
                || $segment === '.'
                || $segment === '..'
                || strpos($segment, '/') !== false
                || strpos($segment, '\\') !== false
                || preg_match('/[\x00-\x1f\x7f]/', $segment)
            ) {
                throw new \InvalidArgumentException('Invalid request path segment.');
            }
            $normalized[] = $segment;
        }

        return '/' . implode('/', $normalized);
    }
}
