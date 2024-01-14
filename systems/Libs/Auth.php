<?php

namespace Flame;

use Flame\Session;

class Auth
{
    public static $allowedActions = [];
    public $controller;

    public $Session;

    public function __construct($controller = null)
    {
        $this->controller = $controller;
        $this->Session = new Session;
        self::$allowedActions = $this->Session->get('allowedAction');
    }
    public function allow($method)
    {
        $controller = $this->controller->name;
        $this->Session->set('allowedAction', array("{$controller}:{$method}" => "{$controller}:{$method}"));
    }
}
