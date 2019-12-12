<?php

namespace dbapi\exception;

use dbapi\tools\App;
use Exception;

class LoggerExceptionCrit extends Exception
{
    public function __construct($message, $code)
    {
        App::$looger->critical($message);
        parent::__construct($message, $code);
    }
}
