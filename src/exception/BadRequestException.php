<?php

namespace dbapi\exception;

use dbapi\tools\App;
use Exception;

class BadRequestException extends Exception
{
    public function __construct($message)
    {
        App::$looger->warning($message);
        parent::__construct($message, 400);
    }
}
