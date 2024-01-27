<?php

namespace Flame;

class Session
{
    function __construct()
    {
        session_save_path(APP . 'tmp/cache/session/');
        session_name('FlameSession');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    function get($name)
    {
        return (!empty($_SESSION[$name]) ? $_SESSION[$name] : NULL);
    }

    function set($name, $value)
    {
        return $_SESSION[$name] = $value;
    }
}
