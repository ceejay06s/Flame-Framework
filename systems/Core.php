<?php

namespace Flame;


class Core
{
    public function __construct()
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestPath = parse_url($requestUri, PHP_URL_PATH);
        if (!is_string($requestPath)) {
            $this->notFound();
            return;
        }

        try {
            $requestPath = Router::normalizePath($requestPath);
        } catch (\InvalidArgumentException $exception) {
            $this->notFound();
            return;
        }

        require APP . 'config' . DS . 'router.php';
        if (!isset($router) || !($router instanceof Router)) {
            throw new \RuntimeException('Application router configuration must provide a Flame\\Router instance.');
        }

        $route = $router->match($requestPath, $requestMethod);
        if ($route === null) {
            $this->notFound();
            return;
        }
        if (!empty($route['method_not_allowed'])) {
            http_response_code(405);
            header('Allow: ' . implode(', ', $route['allow']));
            echo 'Method Not Allowed';
            return;
        }

        $controllerPath = CONTROLLERS . $route['controller'] . '.php';
        if (!is_file($controllerPath)) {
            $this->notFound();
            return;
        }
        require_once $controllerPath;

        $controllerClass = $route['controller'];
        if (!class_exists($controllerClass, false)) {
            throw new \RuntimeException("Configured controller '{$controllerClass}' was not declared by its file.");
        }

        $controller = new $controllerClass();
        if (!method_exists($controller, $route['action'])) {
            $this->notFound();
            return;
        }

        $action = new \ReflectionMethod($controller, $route['action']);
        if (
            !$action->isPublic()
            || $action->isStatic()
            || $action->isConstructor()
            || $action->getDeclaringClass()->getName() !== $controllerClass
        ) {
            $this->notFound();
            return;
        }

        call_user_func_array([$controller, $route['action']], $route['params']);
    }

    private function notFound()
    {
        http_response_code(404);
        echo 'Not Found';
    }
}
