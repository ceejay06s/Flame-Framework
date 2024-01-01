<?php

use Flame\Model;

define('DS', DIRECTORY_SEPARATOR);
define("ROOT", dirname(__DIR__) . DS);
define("SYSTEM", ROOT . 'systems' . DS);
define("APP", ROOT . 'app' . DS);
define("CONTROLLERS", APP . 'controllers' . DS);
//var_dump(DIRECTORY_SEPARATOR);




require_once SYSTEM . 'libs\Logs.php';
require_once SYSTEM . 'libs\Auth.php';
require_once SYSTEM . 'Router.php';
require_once SYSTEM . 'Core.php';
require_once SYSTEM . 'Controller.php';
require_once SYSTEM . 'Model.php';
require_once SYSTEM . 'View.php';

foreach (array_diff(scandir(APP . 'models' . DS), array('..', '.')) as $Model) {
    include APP . "models/$Model";
}
foreach (array_diff(scandir(APP . 'controllers' . DS), array('..', '.')) as $Controllers) {
    include APP . "controllers/$Controllers";
}

$core = new Flame\Core();
