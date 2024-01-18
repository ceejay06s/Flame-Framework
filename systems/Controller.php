<?php


namespace Flame;

useLibrary('Logs', 'libs', SYSTEM);

useLibrary('Auth', 'libs', SYSTEM);

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
    public $Auth;

    private $sets;

    public function __construct()
    {
        $this->Auth = new Auth($this);
        $this->View = new View($this);
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
        require_once APP . 'models' . DS . $ModelName . '.php';
        $ModelFName = "\Flame\Model" . DS . $ModelName;
        $this->controller->$ModelName = $this->$ModelName = new $ModelFName($this);

        return $this;
    }
    public function beforeRender()
    {
        return $this;
    }

    public function set($var, $values)
    {
        $this->sets[$var] = $values;
        return $this;
    }
    public function render($view, $args = [])
    {
        $this->beforeRender();
        $args = array_merge_recursive((!empty($this->sets) ? $this->sets : array()), $args);
        $this->View->render($view, $args);
    }
}
