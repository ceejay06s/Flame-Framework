<?php

namespace Flame;

class Mail
{
    /**
     * _contstruct
     * **/

    private $lines;
    private $mail_path;
    private $values;
    private $keys;


    public $smtp;
    public $username;
    public $password;
    public $port;

    public $body;
    public $subject;

    public $to;

    public $from;

    public $replyTo;

    public $MesageID;

    public function __construct()
    {
        $this->getSendMail();


        return $this;
    }
    function send()
    {
        $variables = get_object_vars($this);
        if ($variables['smtp']) {
            $this->values['smtp_server'] = $variables['smtp'];
        }
        if ($variables['port']) {
            $this->values['smtp_port'] = $variables['port'];
        }
        if ($variables['username']) {
            $this->values['auth_username'] = $variables['username'];
        }
        if ($variables['password']) {
            $this->values['auth_password'] = $variables['password'];
        }
    }
    function get_mail_conf($key)
    {
        return $this->values[$key];
    }

    function getSendMail()
    {

        $sendmail_path = ini_get('sendmail_path');
        list($sendmail_path) = explode(' ', $sendmail_path);
        $this->mail_path = $sendmail_path . '.ini';
        $contents =  file_get_contents($this->mail_path);
        $this->lines = preg_split('/\\r\\n|\\n|\\r/', trim($contents));
        $keys = [];
        $values = [];
        foreach ($this->lines as $line => $val) {


            if (!strstr($val, '; ')) {

                if (strstr($val, '=')) {
                    $dd = explode('=', $val);
                    $this->keys[$dd[0]] = $line;
                    $this->values[$dd[0]] = $dd[1];
                }
            }
        }

        return $this;
    }
}
