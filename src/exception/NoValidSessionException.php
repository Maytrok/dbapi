<?php

namespace dbapi\exception;

use dbapi\tools\App;
use dbapi\tools\HttpCode;
use Exception;

class NoValidSessionException extends Exception
{
    public function __construct($message)
    {
        App::$looger->notice($message);
        parent::__construct($message, HttpCode::UNAUTHORIZED);
    }
}
