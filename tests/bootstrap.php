<?php

use dbapi\db\Database;
use dbapi\tools\App;

include_once __DIR__ . "\\..\\vendor\\autoload.php";

Database::openConnection("root", "");

App::$looger->pushHandler(new \Monolog\Handler\NullHandler());
App::$looger->popProcessor();
