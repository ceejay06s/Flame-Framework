<?php

namespace Flame;

class Session
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_save_path(APP . 'tmp/cache/session/');
            session_start();
        }
        foreach ($_SESSION as $name => $value) {
            $this->{$name} = $value;
        }
    }

    public function set($name, $value)
    {
        $_SESSION[$name] = $value;
    }

    public function get($name)
    {
        return isset($this->{$name}) ? $this->{$name} : NULL;
    }
}
