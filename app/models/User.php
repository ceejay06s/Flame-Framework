<?php

namespace Flame\Model;

use Flame\Model;
use Flame\Auth;

class User extends Model
{
    use \Flame\Authrization;
    public $name = 'User';
    public $useTable = 'users';
}
