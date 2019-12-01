<?php

namespace dbapi\tools;

use Monolog\Formatter\LineFormatter;
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
        self::generateStream();
    }


    public static final function setLogLevel($level)
    {
        self::$loglevel = $level;
        self::generateStream();
    }

    private static final function generateStream()
    {
        $stream = new StreamHandler(App::getLogPath(),  App::$loglevel);
        $format = "[%datetime%] %level_name%: %message% %context% %extra%\n";
        $stream->setFormatter(new LineFormatter($format));
        App::$looger->pushHandler($stream);
    }
}
