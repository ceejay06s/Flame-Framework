<?php

namespace Flame;

trait Authorization
{

    /**
     * @param Array $data (optional) - Array of data credentials such as username/email and password
     * @return Array returns Authenticated Users; 
     * **/
    function login($data = null)
    {
        $this->data = $data ?? $this->controller->data;
        if (!isset($this->name)) {
            $this->name = $this->controller->name;
        }
        if (!isset($this->authFields)) {
            $this->authFields = ['username' => 'username', 'password' => "password"];
        }

        if (!is_array($this->data)) {
            return false;
        }
        $credentials = isset($this->data[$this->name]) && is_array($this->data[$this->name])
            ? $this->data[$this->name]
            : $this->data;
        if (
            !is_array($credentials)
            || !isset($credentials[$this->authFields['username']], $credentials[$this->authFields['password']])
        ) {
            return false;
        }

        $user = $this->find('first', [
                'conditions' => [
                    "{$this->name}.{$this->authFields['username']}" => $credentials[$this->authFields['username']]
                ],
            ]);
        if (
            !isset($user[$this->name][$this->authFields['password']])
            || !$this->passwordVerify(
                (string) $credentials[$this->authFields['password']],
                $user[$this->name][$this->authFields['password']]
            )
        ) {
            return false;
        }

        unset($user[$this->name][$this->authFields['password']]);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['Auth'] = [
            'isLoggedIn' => true,
            'uptime' => time(),
            'expire' => time() + (10 * 365 * 24 * 60 * 60),
            'token' => bin2hex(random_bytes(32)),
            $this->name => $user[$this->name],
        ];

        return $_SESSION;
    }

    /**
     * @param Array $ses SESSION DATA
     * @return Array
     **/
    public function remember_me($ses)
    {
        if (
            !is_array($ses)
            || !isset($this->name)
            || !isset($ses[$this->name]['id'], $ses['token'], $ses['uptime'], $ses['expire'])
        ) {
            return false;
        }

        $this->controller->loadModel('Cookie');
        $cookies = $this->controller->Cookie;
        $cookies->create();
        $cookies->user_id = $ses[$this->name]['id'];
        $cookies->token = $ses['token'];
        $cookies->uptime = $ses['uptime'];
        $cookies->expire_in = $ses['expire'];
        return $cookies->save();
    }


    /**
     * @param String $data text to hash\
     * @param String/Int $hashCode algorithm for hashing
     * @return String Hashed string based on the defined algorithm
     * **/
    function hash($data, $hashCode = "MD5")
    {
        $key = $GLOBALS['securitySalt'] ?? '';
        if ($key === '') {
            throw new \RuntimeException('APP_KEY must be configured before hashing authentication data.');
        }
        return hash_hmac($hashCode, $data, $key);
    }
    /**
     * @param String $data contains String to Hash password
     * @return String hashed password of the $data
     * **/
    function password($data)
    {
        return password_hash($this->hash($data), PASSWORD_DEFAULT);
    }
    /**
     * @param String $data contains String to verify password
     * @param String $verify contains Hashed password to match
     * @return Bool true or false result from verification of password
     * **/
    function passwordVerify($data, $verify)
    {
        return is_string($verify) && password_verify($this->hash($data), $verify);
    }
}
