<?php

namespace Flame;

trait Log
{
    public function Log($log, $file = null)
    {
        Logs::info($log, $file);
    }
}
class  Logs
{
    public $name;
    public function __construct($log, $file = null)
    {
        self::info($log, $file);
    }
    public static function info($log, $file = 'info')
    {
        if (!empty($file)) {
            $handle = fopen(APP . 'tmp' . DS . 'logs' . DS . $file . '.log', 'a+');
            $data = "[" . date('Y-m-d H:i:s', strtotime('now')) . "]:" . var_export($log, true);
            fwrite($handle, $data);
            fclose($handle);
        } else {
            $data =  "[" . date('Y-m-d H:i:s', strtotime('now')) . "]:" . var_export($log, true);
            print_r("<pre>" . $data . "</pre>" . PHP_EOL);
        }
    }
    public static function debug($log, $file = 'debug')
    {
        self::info($log, $file);
    }
    public static function printer($log, $file = null)
    {
        echo self::info($log, $file);
    }
}
