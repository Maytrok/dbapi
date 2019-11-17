<?php

use dbapi\controller\APISimple;
use dbapi\db\Database;
use dbapi\tools\App;
use dbapi\tools\EnvReader;
use Monolog\Logger;

include __DIR__ . "/../bin/basic.php";

//With Debug enabled the output will expose an Trace at each Exception
// App::$DEBUG = true;

// Logging will enabled by default
// To define the output directory just pase an path to the App
// You have to add the logfile name!
App::setLogPath(__DIR__ . "\\logs\\dbapi.log");

// You can manipulate the Log level
App::setLogLevel(Logger::NOTICE);

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

// Init
/**
 * Supported HTTP Methods GET POST PATCH DELETE
 */
$api = new APISimple;

// Hookup an GET Request
$api->setGet(function () {

    // Simple Get Request in which no Params needed
    var_dump($_GET);
});

$api->setPOST(function ($param) {
    // Will fail if no arguments were submitted
    // Will also fail if no greetings Field in Body. This can be in url-encodet or json format
    var_dump($param);
}, ['greetings']);

$api->setPatch(function ($param) {
    // Will fail if no arguments were submitted

    var_dump($param);
});


// DELETE Methods will not be accepted, because the hook is not defined

// Run the Api
$api->run();
