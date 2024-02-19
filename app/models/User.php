<?php

namespace Flame\Model;

use Flame\Model;

\useLibrary('Authorization', 'libs', SYSTEM);

class User extends Model
{
    use \Flame\Authorization;
}
