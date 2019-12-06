<?php

namespace dbapi\exception;

use dbapi\tools\App;
use Exception;

class NoValidSessionException extends Exception
{
    public function __construct($message)
    {
        App::$looger->notice($message);
        parent::__construct($message, 401);
    }
}
