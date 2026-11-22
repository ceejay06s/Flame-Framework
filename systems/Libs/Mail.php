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
    public $service;
    public $curlEndpoint;
    public $curlApiKey;
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
        global $smtp_curl_url, $smtp_curl_api_key;

        $this->service = strtolower((string) ($smtp_service ?? 'native'));
        $this->XMailer = $smtp_XMailer ?? 'Flame Framework';

        $this->smtp = $smtp_server;
        $this->port = $smtp_port;
        $this->protocol = $smtp_protocol;
        $this->username = $smtp_username;
        $this->password = $smtp_password;
        $this->curlEndpoint = $smtp_curl_url ?? '';
        $this->curlApiKey = $smtp_curl_api_key ?? '';

        switch ($this->service) {
            case "native":
                //
                break;
            case "sendmail":
                $this->getSendMail();
                break;
            case "phpmailer":
                $this->mailer = new PHPMailer(true);
                break;
            case "curl":
                break;
            default:
                throw new \InvalidArgumentException("Unsupported mail transport '{$this->service}'.");
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

            $this->mailer->SMTPDebug = SMTP::DEBUG_OFF;
            $this->mailer->isSMTP();                                            //Send using SMTP
            $this->mailer->Host       = $variables['smtp'];                     //Set the SMTP server to send through
            $this->mailer->SMTPAuth   = true;                                   //Enable SMTP authentication
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
        $this->to = isset($recipient) ? $recipient : $this->to;
        $this->subject = isset($subject) ? $subject : $this->subject;
        $this->body = isset($body) ? $body : $this->body;
        $this->header = isset($header) ? $header : $this->header;
        $this->params = isset($params) ? $params : $this->params;

        if ($this->service === 'curl') {
            return $this->sendCurl();
        }

        $this->_set();
        if (!$this->mailer) {
            return mail($this->to, $this->subject, $this->body, implode("\r\n", $this->header), $this->params);
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
                else $this->mailer->addReplyTo($this->replyTo);
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

    private function sendCurl()
    {
        if ($this->curlEndpoint === '' || !filter_var($this->curlEndpoint, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('MAIL_CURL_ENDPOINT must be a valid URL for the cURL mail transport.');
        }
        if (strtolower((string) parse_url($this->curlEndpoint, PHP_URL_SCHEME)) !== 'https') {
            throw new \InvalidArgumentException('The cURL mail endpoint must use HTTPS.');
        }
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('The cURL extension is required for the cURL mail transport.');
        }
        if (empty($this->to) || $this->subject === null || $this->body === null) {
            throw new \InvalidArgumentException('Recipient, subject, and body are required to send email.');
        }

        $payload = json_encode([
            'to' => $this->to,
            'from' => $this->from,
            'from_name' => $this->fromName,
            'reply_to' => $this->replyTo,
            'subject' => $this->subject,
            'body' => $this->body,
            'html' => true,
        ], JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            throw new \RuntimeException('Could not encode the email request as JSON.');
        }

        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if ($this->curlApiKey !== '') {
            if (preg_match('/[\r\n]/', $this->curlApiKey)) {
                throw new \InvalidArgumentException('MAIL_CURL_API_KEY must not contain line breaks.');
            }
            $headers[] = 'Authorization: Bearer ' . $this->curlApiKey;
        }

        $handle = curl_init($this->curlEndpoint);
        if ($handle === false) {
            throw new \RuntimeException('Could not initialize the cURL mail request.');
        }

        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($handle);
        $error = curl_error($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        if ($response === false) {
            throw new \RuntimeException('cURL mail request failed: ' . $error);
        }
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException("Mail API returned HTTP {$status}: " . (string) $response);
        }

        return true;
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
