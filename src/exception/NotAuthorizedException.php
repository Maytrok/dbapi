<?php

namespace dbapi\exception;

use dbapi\tools\App;
use Exception;

class NotAuthorizedException extends Exception
{
    public function __construct($message = null)
    {

        $message = $message ? $message : "Not Authorized";
        App::$looger->notice($message);
        parent::__construct($message, 401);
    }
}
