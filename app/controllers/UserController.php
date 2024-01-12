<?php

use Flame\Controller\AppController;

useLibrary('Authorization', 'libs', SYSTEM);
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

        $this->log($this->User->login());
    }
}
