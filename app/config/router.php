<?php

use Flame\Router;

$router = new Router;

$router->addRoute('/', HomeController::class, 'index');
$router->addRoute('/home', HomeController::class, 'index');
$router->addRoute('/home/index', HomeController::class, 'index');
$router->addRoute('/home/about', HomeController::class, 'about');
$router->addRoute('/user/login', UserController::class, 'login', [], ['GET', 'POST']);
$router->addRoute('/user/register', UserController::class, 'register', [], ['GET', 'POST']);
