<?php

use dbapi\controller\Authenticate;
use dbapi\db\Database;
use dbapi\tools\EnvReader;
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

include_once __DIR__ . '/../../autoload.php';
include_once __DIR__ . '/class/basic/UsersBasic.php';
include_once __DIR__ . '/class/User.php';

// App::$DEBUG = true;

/**
 * Authentification with an User Model and JWT autentification
 * POST Request to Login => Body Parameter required user = username , password = userpassword
 */
$user = new User();
$auth = new Authenticate($user);

$auth->run();
