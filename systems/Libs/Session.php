<?php

namespace Flame;

class Session
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $sessionPath = getenv('SESSION_PATH');
        if ($sessionPath === false || $sessionPath === '') {
            $sessionPath = APP . 'tmp' . DS . 'cache' . DS . 'session';
        }
        if (!is_dir($sessionPath) || !is_writable($sessionPath)) {
            throw new \RuntimeException('The configured session directory must exist and be writable.');
        }

        $secureCookie = getenv('SESSION_COOKIE_SECURE');
        if ($secureCookie === false || $secureCookie === '') {
            $secureCookie = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
        } else {
            $secureCookie = filter_var($secureCookie, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($secureCookie === null) {
                throw new \InvalidArgumentException('SESSION_COOKIE_SECURE must be a boolean value.');
            }
        }

        session_name('FlameSession');
        if (
            ini_set('session.use_strict_mode', '1') === false
            || ini_set('session.use_only_cookies', '1') === false
            || ini_set('session.use_trans_sid', '0') === false
        ) {
            throw new \RuntimeException('Unable to enable secure PHP session settings.');
        }
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secureCookie,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_save_path($sessionPath);

        if (!session_start()) {
            throw new \RuntimeException('Unable to start the PHP session.');
        }
    }

    public function get($name)
    {
        return isset($_SESSION) && array_key_exists($name, $_SESSION) ? $_SESSION[$name] : null;
    }

    public function set($name, $value)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new \RuntimeException('Cannot write to a session that is not active.');
        }
        return $_SESSION[$name] = $value;
    }
}
