<?php

namespace Flame;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mail
{
    /**
     * @var Array $lines Array of SendMail Content
     **/
    private $lines;
    /**
     * @var String $mail_path SendMail Directory
     **/
    private $mail_path;
    /**
     * @var Array $values Array of SendMail Active Value
     **/
    private $values;
    /**
     * @var Array $values Array of SendMail Line Indexes
     **/
    private $keys;
    /**
     * @var String $smtp SMTP Server or Relay Server
     **/
    public $smtp;
    /**
     * @var String $username SMTP Server Username or Relay Server Username
     **/
    public $username;
    /**
     * @var String $password SMTP Server Password or Relay Server Password
     **/
    public $password;
    public $port;
    /**
     * @var String $protocol choose from AUTO NONE SSL TLS
     **/
    public $protocol;
    /**
     * @var String $body Email Body content
     **/
    public $body;
    /**
     * @var String $subject Email Subject
     **/
    public $subject;
    /**
     * @var String $to Recipient Email
     **/
    public $to;
    /**
     * @var String $to Recipient Name
     **/
    public $toName;
    /**
     * @var String $from Sender Email
     **/
    public $from;
    /**
     * @var String $from Sender Name
     **/
    public $fromName;
    /**
     * @var String $from ReplyTo Email
     **/
    public $replyTo;
    /**
     * @var String $from ReplyTo Name
     **/
    public $replyToName;

    public $cc;
    public $bcc;

    public $header = array();
    public $params  = '';

    public $MesageID;

    private $mailer;

    private $XMailer;

    public $attachments = array();
    public $lastMessageID;

    /**
     * @return Class Mail
     * **/

    public function __construct()
    {
        global $smtp_XMailer, $smtp_service, $smtp_server, $smtp_port, $smtp_protocol, $smtp_username, $smtp_password;


        $this->XMailer = $smtp_XMailer;

        $this->smtp = $smtp_server;
        $this->port = $smtp_port;
        $this->protocol = $smtp_protocol;
        $this->username = $smtp_username;
        $this->password = $smtp_password;

        switch ($smtp_service) {
            case "native":
                //
                break;
            case "sendmail":
                $this->getSendMail();
                break;
            case "PhpMailer":
                try {
                    $this->mailer = new PHPMailer(true);
                } catch (Exception $e) {
                    //
                }
                break;
            default:
                $this->getSendMail();
        }

        return $this;
    }
    private function _set()
    {
        $variables = get_object_vars($this);
        if (!$this->mailer) {
            $this->values['smtp_server'] = ($variables['smtp']) ? $variables['smtp'] : null;
            $this->values['smtp_port'] = ($variables['port']) ? $variables['port'] : null;
            $this->values['auth_username'] = ($variables['username']) ? $variables['username'] : null;
            $this->values['auth_password'] = ($variables['password']) ? $variables['password'] : null;
            $this->values['smtp_ssl'] = ($variables['protocol']) ? $variables['protocol'] : null;


            foreach ($this->values as $fields => $value) {
                $this->lines[$this->keys[$fields]] = "{$fields}={$value}";
            }
            file_put_contents($this->mail_path, implode(PHP_EOL, $this->lines));
        } else {

            $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
            $this->mailer->isSMTP();                                            //Send using SMTP
            $this->mailer->Host       = $variables['smtp'];                     //Set the SMTP server to send through
            $this->mailer->SMTPAuth   = 'true';                                   //Enable SMTP authentication
            $this->mailer->Username   = $variables['username'];                     //SMTP username
            $this->mailer->Password   = $variables['password'];                               //SMTP password
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            if ($variables['protocol'] == 'ssl') {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;          //Enable implicit TLS encryption
            }


            $this->mailer->Port       = $variables['port'];
            $this->mailer->XMailer    = $this->XMailer;
        }
    }
    /**
     * @param String $recipient Recipient Email Address
     * @param String $subject Email Subject
     * @param String $body Email Body Content
     * @param Array $header list of Request Header
     * @param String $params Optional Parameter after header
     * @return Any returns true or false or 
     * **/
    function send($recipient = null, $subject = null, $body = null, $header = null, $params = '')
    {
        $this->_set();
        $this->to = isset($recipient) ? $recipient : $this->to;
        $this->subject = isset($subject) ? $subject : $this->subject;
        $this->body = isset($body) ? $body : $this->body;
        $this->header = isset($header) ? $header : $this->header;
        $this->params = isset($params) ? $params : $this->params;

        if (!$this->mailer) {
            return mail($this->to, $this->subject, $this->body, implode('\r\n', $this->header), $this->params);
        } else {
            if (!empty($this->fromName)) $this->mailer->setFrom($this->from, $this->fromName);
            else $this->mailer->setFrom($this->from);

            if (is_array($this->to)) {
                foreach ($this->to as $to) {
                    $this->mailer->addAddress($to);
                }
            } else {
                if (!empty($this->toName)) $this->mailer->addAddress($this->to, $this->toName);
                else $this->mailer->addAddress($this->to);
            }

            if (!empty($this->replyTo)) {
                if (!empty($this->replyToName)) $this->mailer->addReplyTo($this->replyTo, $this->replyToName);
                else $this->mailer->addAddress($this->replyTo);
            }

            if (!empty($this->cc)) {
                if (is_array($this->cc)) {
                    foreach ($this->cc as $cc) {
                        $this->mailer->addCC($cc);
                    }
                } else {
                    $this->mailer->addCC($this->cc);
                }
            }
            if (!empty($this->bcc)) {
                if (is_array($this->bcc)) {
                    foreach ($this->bcc as $bcc) {
                        $this->mailer->addBCC($bcc);
                    }
                } else {
                    $this->mailer->addBCC($this->bcc);
                }
            }

            if (!empty($this->attachments)) {
                foreach ($this->attachments as $attachment) {
                    $this->mailer->addAttachment($attachment);
                }
            }

            $this->mailer->isHTML(true);                                  //Set email format to HTML
            $this->mailer->Subject = $this->subject;
            $this->mailer->Body    = $this->body;
            $send = $this->mailer->send();
            $this->lastMessageID = $this->mailer->getLastMessageID();
            return $send;
        }
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
