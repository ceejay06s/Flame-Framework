<?php

namespace Flame;

class Auth
{
    use Authorization;
    protected $allowedActions = [];
    public $controller;

    public function __construct()
    {
        //
    }

    public function allow($method, $class = $this->controller)
    {
        //
    }
}
