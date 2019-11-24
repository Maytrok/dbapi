<?php

namespace dbapi\tools;

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

    public static final function getLogPath()
    {
        return self::$logpath == "" ? __DIR__ . "..\\..\\..\\..\\..\\dbapi.log" : self::$logpath;
    }

    public static final function setLogPath($logpath)
    {
        self::$logpath = $logpath;

        App::$looger->pushHandler(new StreamHandler(App::getLogPath(), App::$loglevel));
    }


    public static final function setLogLevel($level)
    {

        self::$loglevel = $level;
        App::$looger->pushHandler(new StreamHandler(App::getLogPath(), App::$loglevel));
    }
}
