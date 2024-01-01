<?php

use Flame\Controller\AppController;

use Flame\Model\User;



class UserController extends AppController
{
    var $name = "User";

    function login()
    {
        $mode = new User;
        //$mode->id = 1;
        $test = $mode->find('last', array(
            'fields' => ['User.*', ['UserDetail.*']],
            'conditions' => ['User.username' => 'ceejay'],
            'joins' => [
                [
                    'type' => 'left',
                    'table' => 'user_details',
                    'alias' => 'UserDetail',
                    'conditions' => ['UserDetail.user_id = User.id'],
                ]
            ]
        ));
        $this->log($test);
        $this->render('users/login', ['data' => 'test data']);
    }
}
