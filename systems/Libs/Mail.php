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
    /**
     * @var $protocol choose from AUTO NONE SSL TLS
     * **/
    public $protocol;

    public $body;
    public $subject;

    public $to;

    public $from;

    public $replyTo;

    public $header = array();
    public $params  = '';

    public $MesageID;

    public function __construct()
    {
        global $smtp_service, $smtp_server, $smtp_port, $smtp_protocol, $smtp_username, $smtp_password;
        $this->smtp = $smtp_server;
        $this->port = $smtp_port;
        $this->protocol = $smtp_protocol;
        $this->username = $smtp_username;
        $this->username = $smtp_password;

        switch ($smtp_service) {
            case "native":
                //
                break;
            case "sendmail":
                $this->getSendMail();
                break;
            case "PhpMailer":
                break;
            default:
                $this->getSendMail();
        }

        return $this;
    }
    private function _set()
    {
        $variables = get_object_vars($this);
        $this->values['smtp_server'] = ($variables['smtp']) ? $variables['smtp'] : NULL;
        $this->values['smtp_port'] = ($variables['port']) ? $variables['port'] : NULL;
        $this->values['auth_username'] = ($variables['username']) ? $variables['username'] : NULL;
        $this->values['auth_password'] = ($variables['password']) ? $variables['password'] : NULL;
        $this->values['smtp_ssl'] = ($variables['protocol']) ? $variables['protocol'] : NULL;


        foreach ($this->values as $fields => $value) {
            $this->lines[$this->keys[$fields]] = "{$fields}={$value}";
        }
        file_put_contents($this->mail_path, implode(PHP_EOL, $this->lines));
    }
    function send()
    {
        $this->_set();
        return mail($this->to, $this->subject, $this->body, implode('\r\n', $this->header), $this->params);
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
