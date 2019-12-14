<?php

use dbapi\controller\APISimple;
use dbapi\db\Database;
use dbapi\tools\App;
use dbapi\tools\EnvReader;
use dbapi\views\JsonView;
use Monolog\Logger;

include __DIR__ . "/../bin/basic.php";

//With Debug enabled the output will expose an Trace at each Exception
// App::$DEBUG = true;

// By Default all params for an POST or PATCH requests will be sanitizes using filter_var($X, FILTER_SANITIZE_SPECIAL_CHARS);
// The following line disable this behavior

//APISimple::$SANITIZE_INPUT = false;

// Logging will enabled by default
// To define the output directory just pase an path to the App
// You have to add the logfile name!
App::setLogPath(__DIR__ . "\\logs\\dbapi.log");

// You can manipulate the Log level
App::setLogLevel(Logger::NOTICE);

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

/**
 * Supported HTTP Methods GET POST PATCH DELETE
 */
$api = new APISimple;

// Hookup an GET Request
$api->setGet(function () {

    $view = new JsonView;

    $view->setMainData(["Result" => "From get Request"]);
    return $view;
});

$api->setPOST(function ($param) {
    // Will fail if no arguments were submitted
    // Will also fail if no greetings Field in Body. This can be in url-encodet or json format
    $view = new JsonView;
    $view->setMainData(["Result" => "From GET Request", "param" => $param]);
    return $view;
}, ['greetings']);

$api->setPatch(function ($param) {
    // Will fail if no arguments were submitted

    $view = new JsonView;
    $view->setMainData(["Update" => "Something was updated"]);
    return $view;
});


// DELETE Methods will not be accepted, because the hook is not defined

// Run the Api
$api->run();
