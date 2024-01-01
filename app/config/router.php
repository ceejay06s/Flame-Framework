<?php

use Flame\Router;

$router = new Router;
$router->addRoute('/user/log', UserController::class, 'login');
