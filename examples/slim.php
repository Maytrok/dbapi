<?php

use dbapi\controller\SlimAdapter;
use dbapi\db\Database;
use dbapi\tools\App;
use dbapi\tools\EnvReader;
use php\klassen\Content;
use php\klassen\User;

include __DIR__ . "/../bin/basic.php";


// DB Init
try {
    Database::open(new EnvReader(__DIR__));
    // for older php Versions E_STRICT should be disabled
    error_reporting(E_ERROR);
} catch (\Exception $th) {
    // Fatal Error with the Database Connection
    App::$looger->emergency("Connection to Database failed!");
    exit();
}


// Should be in an autoloader
include_once './class/basic/UserBasic.php';
include_once './class/User.php';
include_once './class/basic/ContentBasic.php';
include_once './class/Content.php';

class UserController extends SlimAdapter
{

    protected function getSlimModel()
    {
        return new User;
    }
}

class ContentController extends SlimAdapter
{

    protected function getSlimModel()
    {
        return new Content;
    }
}


$app = new \Slim\App;

// The Full CRUD operations work within this routes
$app->any('/user[/{id}]', UserController::class);
$app->any('/content[/{id}]', ContentController::class);

$app->run();
