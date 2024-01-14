<?php

use Flame\Controller\AppController;

useLibrary('Authorization', 'libs', SYSTEM);
class UserController extends AppController
{
    public $name = "User";

    public $useModels = array();

    public function login()
    {
        $this->controller->data['test1'] = 'test';
        $this->loadModel('User');
        if ($this->User->login()) {
            var_dump("test");
        }
    }
}
