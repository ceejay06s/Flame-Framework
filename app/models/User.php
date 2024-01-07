<?php

namespace Flame\Model;

use Flame\Model;

class User extends Model
{
    use \Flame\Authrization;

    public $name = 'User';
}
