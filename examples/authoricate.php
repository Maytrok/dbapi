<?php

use dbapi\controller\Api;
use dbapi\db\Database;
use dbapi\interfaces\AuthoricateMethod;
use dbapi\tools\App;
use php\klassen\Content;
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

// Alternative open with credentials
// Database::openConnection("myUser", "5up3r53cr37");


// Should be in an autoloader
include_once './class/basic/ContentBasic.php';
include_once './class/Content.php';
include_once './class/User.php';

// By implementing the Authoricate Interface the Api controller will check if the User is Authoricate to use the request method
// All Methods has to return an bool value
class ContentUser extends User implements AuthoricateMethod
{

    public function allowGet()
    {
        return true;
    }
    public function allowPost()
    {
        return strtolower($this->getName()) == "admin";
    }
    public function allowPatch()
    {
        return false;
    }
    public function allowDelete()
    {
        return strtolower($this->getName()) == "admin";
    }
}

// The Authoricate Class does not need extend an Model 
class CustomCheck implements AuthoricateMethod
{

    private function validIp()
    {
        return $_SERVER['REMOTE_ADDR'] != "192.168.13.37";
    }

    public function allowGet()
    {
        return $this->validIp();
    }
    public function allowPost()
    {
        return $this->validIp();
    }
    public function allowPatch()
    {
        return $this->validIp();
    }
    public function allowDelete()
    {
        return $this->validIp();
    }
}

Api::setAuth(new ContentUser);
$api = new Api(new Content);

$api->run();
