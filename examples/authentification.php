<?php

// See also loginLogout.php

use dbapi\controller\Api;
use dbapi\db\Database;
use dbapi\exception\NotAuthorizedException;
use dbapi\tools\App;
use php\klassen\Content;
use dbapi\tools\EnvReader;
use php\klassen\User;

include __DIR__ . "/../bin/basic.php";

// App::$DEBUG = true;

// DB Init
try {
    Database::open(new EnvReader(__DIR__));
} catch (\Throwable $th) {
    // Fatal Error with the Database Connection
    App::$looger->emergency("Connection to Database failed!");
    exit();
}

// Alternative open with credentials
// Database::openConnection("myUser", "5up3r53cr37");

// Should be in an autoloader
include_once './class/basic/ContentBasic.php';
include_once './class/Content.php';
include_once './class/basic/UsersBasic.php';
include_once './class/User.php';

// Autoload für JWT Lib
include_once __DIR__ . '/../../autoload.php';

$user = new User;
$content = new Content();

/**
 * The User Model will initilized by the authentication Process. In this case with an JWT Token which has to be provided in the request header
 * If failed the Programm will quit
 * To Parse an Model into the Authentification process the Interface RestrictedView is required
 * If provided the results are Limited to the current user
 */
$user->authenticate($content);
$api = new Api($content);

// This hook is also available without Authentication
$api->hookAuth(function (Content $model, $REQUEST_METHOD) {
    // Does not Work for GET METHODS

    // denial a request method for an specific user
    if ($REQUEST_METHOD == "POST" && User::getModel()->getName() == "someName") {

        throw new NotAuthorizedException();
    }

    // prohibits an action based on the Model State
    if (in_array($REQUEST_METHOD, ["PATCH", "DELETE"]) && $model->getComplete() == 1) {
        throw new Exception("Action is not Allowed", 409);
    }
});


$api->run();
