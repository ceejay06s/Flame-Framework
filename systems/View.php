<?php

namespace Flame;

class View
{
    public $layout;
    public $data;
    public static $contentLayout;
    public  $titleLayout;
    public $controller;

    public function __construct(&$controller = null)
    {
        $this->controller = $controller;
    }

    public function berforeRender()
    {
    }
    function render($_view, $data = [])
    {
        $titleLayout = $this->titleLayout;
        extract($data);
        ob_start();
        if (strstr($_view, '/')) {
            if (file_exists(APP . "views/$_view.php")) {
                include_once APP . "views/$_view.php";
            } else {
                throw new \Exception("No View found");
            }
        } else {
            if (file_exists(APP . "views/{$this->controller->name}/$_view.php")) {
                include_once APP . "views/{$this->controller->name}/$_view.php";
            } else {
                throw new \Exception("No View found");
            }
        }

        $contentLayout = ob_get_contents();
        ob_end_clean();
        if (!empty($this->layout)) {
            if (file_exists(APP . "views/layouts/$this->layout.php")) {
                include_once APP . "views/layouts/$this->layout.php";
            } else {
                throw new \Exception("No Layout found");
            }
        } else print_r($contentLayout);
    }
}
