<?php

use \Sokoro\ORM as Sokoro;

require_once "sokoro";

ORM::$table = "users";

print $row->id;
print $row->username;