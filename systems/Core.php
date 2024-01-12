<?php

namespace Flame;

class Core
{
    protected $controller = 'HomeController';
    protected $method = 'index';
    protected $params = [];
    public function __construct()
    {
        $this->parseUrl();
        $controllerPath = CONTROLLERS . $this->controller . '.php';


        if (file_exists($controllerPath)) {
            include $controllerPath;
            $this->controller = new $this->controller;
            if (method_exists($this->controller, $this->method)) {
                call_user_func_array([$this->controller, $this->method], $this->params);
            } else {
                echo "Method does not exist.";
            }
        } else {
            echo "Controller does not exist.";
        }
    }

    protected function parseUrl()
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
            $url = rtrim($uri, '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            $url = trim($url);
            $url = explode('/', $url);
            $url['url']  = $url[0];
            if (isset($url[1], $url[2]))
                $url['url'] = '/' . $url[1] . '/' . $url[2];
            require_once APP . "config/router.php";
            if (array_key_exists($url['url'], $router->routes)) {
                $this->controller = $router->routes[$url['url']]['controller'];
                $this->method = $router->routes[$url['url']]['action'];
                $this->params = $router->routes[$url['url']]['params'];
            } else {
                $this->controller = !empty($url[0]) ? ucfirst($url[0]) . 'Controller' : 'HomeController';
                $this->method = isset($url[1]) ? $url[1] : 'index';
                $this->params = array_slice($url, 3);
            }
        }
    }
}
