<?php

namespace Flame;

use \ICanBoogie\Inflector as Inflectors;

class Inflector
{
    static function countable($text)
    {
        $inf = Inflectors::get(Inflectors::DEFAULT_LOCALE);
        return $inf->is_uncountable($text) ? false : true;
    }
    static function ordinal($text, $type)
    {
        $$inf = Inflectors::get(Inflectors::DEFAULT_LOCALE);
        if ($type == 2) return $inf->ordinalize($text);

        else $inf->ordinal($text);
    }
    static function titleize($text)
    {
        $inf = Inflectors::get(Inflectors::DEFAULT_LOCALE);
        return $inf->titleize($text);
    }
    static function humanize($text)
    {
        $inf = Inflectors::get(Inflectors::DEFAULT_LOCALE);
        return $inf->humanize($text);
    }
    static function pluralize($text)
    {
        $inf = Inflectors::get(Inflectors::DEFAULT_LOCALE);
        return $inf->pluralize($text);
    }
    static function singularize($text)
    {
        $inf = Inflectors::get(Inflectors::DEFAULT_LOCALE);
        return $inf->singularize($text);
    }
    static function underscore($text)
    {
        $inf = Inflectors::get(Inflectors::DEFAULT_LOCALE);
        return $inf->underscore($text);
    }

    static function dashilize($text)
    {
        $text = self::underscore($text);
        $text = str_replace('_', '-', $text);
        return $text;
    }
    static function camelize($text, $type = NULL)
    {
        $inf = Inflectors::get(Inflectors::DEFAULT_LOCALE);
        switch ($type) {
            case 1:
            case "UPCASE_FIRST_LETTER":
                return $inf->camelize($text, Inflectors::UPCASE_FIRST_LETTER);
            case 2:
            case "DOWNCASE_FIRST_LETTER":
                return $inf->camelize($text, Inflectors::DOWNCASE_FIRST_LETTER);
            default:
                return $inf->camelize($text);
        }
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
