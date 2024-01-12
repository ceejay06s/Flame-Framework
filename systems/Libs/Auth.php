<?php

namespace Flame;


trait Authrization
{
    public $sessionTimeout = 1800;

    function login()
    {
        //var_dump($this->data);
        if (!isset($this->authFields)) {
            $this->authFields = ['username' => 'username', 'password' => "password"];
        }
        if (isset($this->data[$this->name][$this->authFields['username']], $this->data[$this->name][$this->authFields['password']])) {
            $tmp =  $this->find('first', array(
                'conditions' => [
                    "User.{$this->authFields['username']}" => $this->data[$this->name][$this->authFields['username']]
                ],
            ));

            $verify = $this->passwordVerify($this->data[$this->name][$this->authFields['password']], $tmp[$this->name][$this->authFields['password']]);
            if ($verify) {
                unset($tmp[$this->name][$this->authFields['password']]);

                $_SESSION['Auth']['isLoggedIn'] = true;
                $_SESSION['Auth']['uptime'] = time();
                $_SESSION['Auth']['expire'] = time() + (10 * 365 * 24 * 60 * 60);
                $_SESSION['Auth'][$this->name] = $tmp[$this->name];
                return $_SESSION;
            }
        }
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

    function password($data)
    {
        return \password_hash($this->hash($data), PASSWORD_DEFAULT);
    }

    function passwordVerify($data, $verify)
    {
        return \password_verify($this->hash($data), $verify);
    }
}
class Auth
{
    public $allowed = [];
    public $controller;
    public function __construct(&$controller = null)
    {
        if (!empty($controller))
            $this->controller =  $controller;
    }
    public function allow()
    {

        $actions = func_get_args();
        var_dump($this->controller->name);
    }
}
