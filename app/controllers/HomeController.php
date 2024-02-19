<?php

use Flame\Controller\AppController;

class HomeController extends AppController
{
    var $useModels = array('User');
    public function index()
    {
        print_r($this->User->first("SELECT * FROM users"));
        echo "Welcome to the home page!";
    }

    public function about()
    {
        echo "This is the about page.";
    }
}
