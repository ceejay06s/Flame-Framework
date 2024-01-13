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
        $mail->to = "christianbalaisvi@gmail.com";
        $mail->from = "christianbalais06@gmail.com";
        $mail->subject = "testing";
        $mail->body = 'test12345';
        echo "<pre>";
        var_dump($mail->send());
    }
}
