<?php

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__DIR__) . DS);
define('SYSTEM', ROOT . 'systems' . DS);
define('APP', ROOT . 'app' . DS);

if (!is_file(APP . 'vendor' . DS . 'autoload.php')) {
    throw new RuntimeException('Composer dependencies are missing; run composer install --working-dir=app.');
}
require_once APP . 'vendor' . DS . 'autoload.php';

$config = static function ($name, $value) {
    if (strpos($name, 'ini__') === 0) {
        ini_set(substr($name, 5), $value);
        return;
    }
    $GLOBALS[$name] = $value;
};

function useLibrary($library, $directory = 'libs', $path = SYSTEM)
{
    $file = $path . $directory . DS . $library . '.php';
    if (!is_file($file)) {
        throw new RuntimeException("Framework library '{$library}' was not found.");
    }
    require_once $file;
}

require APP . 'config' . DS . 'core.php';
useLibrary('Inflector');
require_once SYSTEM . 'Model.php';
require_once SYSTEM . 'Libs' . DS . 'Scheduler.php';

$registerTasks = require APP . 'config' . DS . 'schedule.php';
if (!is_callable($registerTasks)) {
    throw new RuntimeException('app/config/schedule.php must return a task-registration callback.');
}

$scheduler = new Flame\Scheduler();
$registerTasks($scheduler);
$scheduler->run();
