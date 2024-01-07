<?php

namespace Flame;

use Flame\View;


class Controller
{
    use Log;
    public $name;
    public $data = [];
    public $controller;
    public $View;
    public $layout = 'default';
    public $titleLayout = 'Flame';

    public $useModels = [];

    public function __construct()
    {
        //session_start();
        $this->View = new View;
        $this->View->layout = $this->layout;
        $this->View->titleLayout = $this->titleLayout;
        $this->controller = $this;

        $this->data = (!empty($_REQUEST['data']) ? $_REQUEST['data'] : $_REQUEST);
        foreach ($this->useModels as $ModelName) {
            $this->loadModel($ModelName);
        }
    }

    public function loadModel($ModelName)
    {
        include APP . 'models' . DS . $ModelName . '.php';
        $ModelFName = "\Flame\Model" . DS . $ModelName;
        $this->$ModelName = new $ModelFName($this);
        return $this;
    }
    public function beforeRender()
    {
        return $this;
    }
    public function render($view, $args = [])
    {
        $this->beforeRender();

        $this->View->render($view, $args);
    }
}
