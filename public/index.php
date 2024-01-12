<?php
define('DS', DIRECTORY_SEPARATOR);
define("ROOT", dirname(__DIR__) . DS);
define("SYSTEM", ROOT . 'systems' . DS);
define("APP", ROOT . 'app' . DS);
define("CONTROLLERS", APP . 'controllers' . DS);

session_start();

$config = function ($conf, $value) {
    $GLOBALS[$conf] = $value;
};

function useLibrary($lib, $dir = 'libs', $path = SYSTEM)
{
    // var_dump($lib);
    require_once $path . $dir . DS . $lib . '.php';
};
require_once APP . 'config/core.php';

// require_once SYSTEM . 'libs\Logs.php';
// require_once SYSTEM . 'libs\Authorization.php';
require_once SYSTEM . 'libs\Inflector.php';
require_once SYSTEM . 'Router.php';
require_once SYSTEM . 'Core.php';
require_once SYSTEM . 'Controller.php';
require_once SYSTEM . 'Model.php';
require_once SYSTEM . 'View.php';
require_once CONTROLLERS . 'AppController.php';

$core = new Flame\Core();
