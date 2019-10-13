<?php

namespace dbapi\exception;

use Exception;

class RequestMethodNotAllowedException extends Exception
{
    public function __construct($message = null)
    {
        $message = $message ? $message : "Request Method not Allowed";
        parent::__construct($message, 405);
    }
}
