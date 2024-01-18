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
        $this->data = $this->controller->data;
        if (!isset($this->name)) {
            $this->name = $this->controller->name;
        }
        if (empty($this->data) && !empty($data)) {

            if (!isset($this->data[$this->name])) {
                $this->data[$this->name] = $data;
            } else
                $this->data = $data;
        }
        if (!isset($this->authFields)) {
            $this->authFields = ['username' => 'username', 'password' => "password"];
        }



        if (isset($this->data[$this->name][$this->authFields['username']], $this->data[$this->name][$this->authFields['password']])) {
            $tmp =  $this->find('first', array(
                'conditions' => [
                    "User.{$this->authFields['username']}" => $this->data[$this->name][$this->authFields['username']]
                ],
            ));
            // var_dump($tmp[$this->name][$this->authFields['username']]);
            $verify = $this->passwordVerify($this->data[$this->name][$this->authFields['password']], $tmp[$this->name][$this->authFields['password']]);
            if ($verify) {
                unset($tmp[$this->name][$this->authFields['password']]);

                $_SESSION['Auth']['isLoggedIn'] = true;
                $_SESSION['Auth']['uptime'] = time();
                $_SESSION['Auth']['expire'] = time() + (10 * 365 * 24 * 60 * 60);
                $_SESSION['Auth']['token'] = $this->hash(uniqid('_SES_'), 'SHA1');
                $_SESSION['Auth'][$this->name] = $tmp[$this->name];
                $this->remember_me($_SESSION['Auth']);
                return $_SESSION;
            }
        }
    }

    /**
     * @param Array $ses SESSION DATA
     * @return Array
     **/
    public function remember_me($ses)
    {
        $this->controller->loadModel('Cookie');
        $cookies = $this->controller->Cookie;
        $cookies->create();
        $cookies->user_id = $ses[$this->name]['id'];
        $cookies->token = $ses['token'];
        $cookies->uptime = $ses['uptime'];
        $cookies->expire_in = $ses['expire'];
        $cookies->save();
        var_dump(get_object_vars($cookies));
    }


    /**
     * @param String $data text to hash\
     * @param String/Int $hashCode algorithm for hashing
     * @return String Hashed string based on the defined algorithm
     * **/
    function hash($data, $hashCode = "MD5")
    {
        return hash_hmac($hashCode, $data, $GLOBALS['securitySalt']);
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
        return password_verify($this->hash($data), $verify);
    }
}
