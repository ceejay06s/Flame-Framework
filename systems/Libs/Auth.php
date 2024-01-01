<?php

namespace Flame;

trait Authrization
{
}
class Auth
{

    public function __construct()
    {
        session_start();
    }
}
