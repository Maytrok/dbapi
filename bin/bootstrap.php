<?php

namespace dbapi\bin;

use dbapi\controller\ApiSimple;
use dbapi\tools\App;

use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;
use Monolog\Logger;


// Create the logger
App::$looger = new Logger('dbapi_default_logger');
// Now add some handlers
App::$looger->pushHandler(new StreamHandler(App::getLogPath(), Logger::ERROR));
App::$looger->pushHandler(new FirePHPHandler());
App::$looger->pushProcessor(function ($record) {
    $record['extra']['defaults'] = ["ip" => $_SERVER['REMOTE_ADDR'], "requesturi" => $_SERVER['REQUEST_URI'], "parms" => ApiSimple::getParamBody()];

    return $record;
});
