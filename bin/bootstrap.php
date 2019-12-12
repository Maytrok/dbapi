<?php

namespace dbapi\bin;

use dbapi\controller\ApiSimple;
use dbapi\tools\App;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;
use Monolog\Logger;


// Create the logger
App::$looger = new Logger('dbapi_default_logger');

$stream = new StreamHandler(App::getLogPath(), Logger::ERROR);
$format = "[%datetime%] %level_name%: %message% %context% %extra%\n";
$stream->setFormatter(new LineFormatter($format));
App::$looger->pushHandler($stream);
App::$looger->pushHandler(new FirePHPHandler());
App::$looger->pushProcessor(function ($record) {
    $remote = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "0.0.0.0";
    $requesturi = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : "---";

    $record['extra']['defaults'] = ["ip" => $remote, "requesturi" => $requesturi, "parms" => ApiSimple::getParamBody()];

    return $record;
});
