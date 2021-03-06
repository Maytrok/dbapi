<?php

namespace dbapi\exception;

use dbapi\tools\App;
use Exception;

class RequestMethodNotAllowedException extends Exception
{
    public function __construct($message = null)
    {
        $message = $message ? $message : $_SERVER['REQUEST_METHOD'] . "Request Method not Allowed";

        App::$looger->warning($message);
        parent::__construct($message, 405);
    }
}
