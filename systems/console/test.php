<?php
include_once "WebSocket.php";

use Flame\WebSocket;

class Test extends WebSocket
{
    public function onReceive()
    {
        var_dump($this->data);
    }

    public function onMessage()
    {
        $this->message = 'test1234';
        parent::onMessage();
    }
}


$ee = new Test;
$ee->__init__();
$ee->start();
