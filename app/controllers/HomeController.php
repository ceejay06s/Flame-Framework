<?php

use Flame\Controller\AppController;

class HomeController extends AppController
{
    public function index()
    {
        echo "Welcome to the home page!";
    }

    public function about()
    {
        echo "This is the about page.";
    }
}
