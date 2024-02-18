<?php


define('DS', DIRECTORY_SEPARATOR);
define("ROOT", dirname(__DIR__) . DS);
define("SYSTEM", ROOT . 'systems' . DS);
define("APP", ROOT . 'app' . DS);
define("CONTROLLERS", APP . 'controllers' . DS);

include_once APP . 'vendor/autoload.php';

$config = function ($conf, $value) {
    if (strstr($conf, 'ini__')) {
        $conf = trim($conf, 'ini__');
        ini_set($conf, $value);
    } else
        $GLOBALS[$conf] = $value;
};



function useLibrary($lib, $dir = 'libs', $path = SYSTEM)
{
    require_once $path . $dir . DS . $lib . '.php';
};

useLibrary('Inflector');
useLibrary('Session');

require_once APP . 'config/core.php';
require_once SYSTEM . 'Router.php';
require_once SYSTEM . 'Core.php';
require_once SYSTEM . 'Controller.php';
require_once CONTROLLERS . 'AppController.php';
require_once SYSTEM . 'Model.php';
require_once SYSTEM . 'View.php';
$core = new Flame\Core();
