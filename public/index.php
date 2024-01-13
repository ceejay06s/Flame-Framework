<?php
define('DS', DIRECTORY_SEPARATOR);
define("ROOT", dirname(__DIR__) . DS);
define("SYSTEM", ROOT . 'systems' . DS);
define("APP", ROOT . 'app' . DS);
define("CONTROLLERS", APP . 'controllers' . DS);


include_once APP . 'vendor/autoload.php';

$config = function ($conf, $value) {
    $GLOBALS[$conf] = $value;
};

if (isset($session_path)) ini_set('session.save_path', $session_path);
session_start();

function useLibrary($lib, $dir = 'libs', $path = SYSTEM)
{
    require_once $path . $dir . DS . $lib . '.php';
};

require_once APP . 'config/core.php';
require_once SYSTEM . 'libs\Inflector.php';
require_once SYSTEM . 'Router.php';
require_once SYSTEM . 'Core.php';
require_once SYSTEM . 'Controller.php';
require_once SYSTEM . 'Model.php';
require_once SYSTEM . 'View.php';
require_once CONTROLLERS . 'AppController.php';

$core = new Flame\Core();
