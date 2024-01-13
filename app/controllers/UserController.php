<?php

use Flame\Controller\AppController;

useLibrary('Authorization', 'libs', SYSTEM);
useLibrary('Mail', 'libs', SYSTEM);
class UserController extends AppController
{
    var $name = "User";

    var $useModels = array();
    function __construct()
    {
        parent::__construct();

        //$this->Auth->allow('index', 'register');
    }
    function login()
    {
        $this->controller->data['test1'] = 'test';
        $this->loadModel('User');

        //$this->log($this->User->login());


        $mail = new \Flame\Mail;
        $mail->smtp = "sandbox.smtp.mailtrap.io";
        $mail->port = 25;
        $mail->protocol = "tls";
        $mail->username = "b436de3cde2761";
        $mail->password = "28c65e1ad5d21d";
        $mail->body = "test";
        $mail->subject = "test";
        $mail->header[] = 'FROM: christianbalais06@gmail.com';
        $mail->to = 'christianbalaisvi@gmail.com';
        $mail->send();
    }
}
