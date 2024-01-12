<?php

use Flame\Controller\AppController;


class UserController extends AppController
{
    var $name = "User";
    var $Auth;
    function __construct()
    {
        parent::__construct();
        $this->Auth = new \Flame\Auth($this->controller);

        $this->Auth->allow('test');
    }

    function login()
    {
        $this->controller->data['test1'] = 'test';
        $this->loadModel('User');
        //var_dump($this, 'ev');
        //$mode->id = 1;
        // $test = $this->User->find('last', array(
        //     'fields' => ['User.id', ['UserDetail.*']],
        //     'conditions' => ['User.username' => 'ceejay'],
        //     'joins' => [
        //         [
        //             'type' => 'left',
        //             'table' => 'user_details',
        //             'alias' => 'UserDetail',
        //             'conditions' => ['UserDetail.user_id = User.id'],
        //         ]
        //     ]
        // ));
        // $this->data = 'test';
        // $this->log($mode->statement);
        $this->log($this->User->login());
        //$this->render('users/login', ['data' => '<pre>' . print_r($test, true)]);
    }
}
