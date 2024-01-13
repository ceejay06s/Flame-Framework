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


        $Mail = new \Flame\Mail;
        $Mail->smtp = "sandbox.smtp.mailtrap.io";
        $Mail->body = "test";
        $Mail->subject = "test";
        $Mail->send();
    }
}
