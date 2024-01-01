<?php

namespace Flame;

class Router
{
    public $routes = [];
    public static function redirect($url)
    {
        header("Location: $url");
        exit();
    }

    public  function addRoute($route,  $controller,  $action, $params = array())
    {
        $this->routes[$route] = ['controller' => $controller, 'action' => $action, 'params' => $params];
    }
}
