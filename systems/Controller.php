<?php

namespace Flame;

use Flame\View;


class Controller
{
    use Log;
    public $name;
    public $data;
    public $View;
    public $layout = 'default';
    public $titleLayout = 'Flame';

    public function __construct()
    {

        $this->View = new View;
        $this->View->layout = $this->layout;
        $this->View->titleLayout = $this->titleLayout;
    }
    public function beforeRender()
    {
        return;
    }
    public function render($view, $args = [])
    {
        $this->beforeRender();

        $this->View->render($view, $args);
    }
}
