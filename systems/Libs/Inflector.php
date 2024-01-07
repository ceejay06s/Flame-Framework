<?php

namespace Flame;

class Inflector
{
    static function pluralize($text)
    {
        if (substr($text, -1) == 'y') {
            $text = rtrim($text, 'y') . "ie";
        }

        return $text . 's';
    }

    static function singularize($text)
    {
        $text = rtrim($text, 's');
        if (substr($text, -2) == 'ie') {
            $text = rtrim($text, 'ie') . "y";
        }
        return $text;
    }
    static function snakilize($text)
    {
        // Replace spaces and special characters with underscores
        $text = preg_replace('/[^A-Za-z0-9]+/', '_', $text);
        // Convert to lowercase
        $text = strtolower($text);
        // Remove leading and trailing underscores
        $text = trim($text, '_');
        return $text;
    }

    static function dashilize($text)
    {
        // Replace spaces and special characters with underscores
        $text = preg_replace('/[^A-Za-z0-9]+/', '-', $text);
        // Convert to lowercase
        $text = strtolower($text);
        // Remove leading and trailing underscores
        $text = trim($text, '-');
        return $text;
    }
    static function camelize($text)
    {
        // Replace spaces and special characters with underscores
        $text = Inflector::dashilize($text);
        $ret = null;
        foreach (explode("-", $text) as $words)
            $ret .= ucfirst($words);
        return $ret;
    }

    protected function Log($text, $file = null)
    {
        $datetime = date('Y-m-d H:i:s', strtotime('now'));
        if (empty($file)) {
            echo "<pre>";
            echo "[$datetime]: " . print_r($text, true);
            echo "</pre>";
        } else {
            $handler = fopen(APP . 'tmp' . DS . 'logs' . DS . "$file.log", 'a+', true);
            $datetime = date('Y-m-d H:i:s', strtotime('now'));
            fwrite($handler, "[$datetime]: " . print_r($text, true)) . PHP_EOL;
            fclose($handler);
        }
    }
}
