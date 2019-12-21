<?php

namespace dbapi\tools;

use dbapi\controller\ApiSimple;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\FirePHPHandler;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class App
{

    public static $DEBUG = false;

    private static $logpath = "";

    private static $loglevel = Logger::ERROR;

    /**
     * @var Logger
     */
    public static $looger;

    private static $loggerInit = false;

    public static final function initLogger($pathLogfile)
    {

        $stream = new StreamHandler($pathLogfile, Logger::ERROR);
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

        self::$loggerInit = true;
    }

    public static final function getLogPath()
    {
        return self::$logpath == "" ? __DIR__ . "..\\..\\..\\..\\..\\dbapi.log" : self::$logpath;
    }

    public static final function setLogPath($logpath)
    {
        self::$logpath = $logpath;
        self::generateStream();
    }


    public static final function setLogLevel($level)
    {
        self::$loglevel = $level;
        self::generateStream();
    }

    private static final function generateStream()
    {

        if (self::$loggerInit) {
            $stream = new StreamHandler(App::getLogPath(),  App::$loglevel);
            $format = "[%datetime%] %level_name%: %message% %context% %extra%\n";
            $stream->setFormatter(new LineFormatter($format));
            App::$looger->pushHandler($stream);
        }
    }
}
