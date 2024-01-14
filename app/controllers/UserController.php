<?php

useLibrary('Authorization', 'libs', SYSTEM);

use Flame\Controller\AppController;
use Flame\Session;

class UserController extends AppController
{
    public $name = "User";

    public $useModels = array();

    public function __construct()
    {

        parent::__construct();
        $session = new Session;
        $this->Auth->allow('login');
    }

    public function login()
    {
        $this->controller->data['test1'] = 'test';
        $this->loadModel('User');
        if ($this->User->login()) {
            $this->log("test");
        }
        $this->set('test', 'test1');
        $this->render('login');
    }

    function register()
    {
    }
}
