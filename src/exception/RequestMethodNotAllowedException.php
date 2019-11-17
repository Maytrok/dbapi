<?php

namespace dbapi\exception;

use dbapi\tools\HttpCode;
use Exception;

class RequestMethodNotAllowedException extends Exception
{
    public function __construct($message = null)
    {
        $message = $message ? $message : "Request Method not Allowed";
        parent::__construct($message, HttpCode::METHOD_NOT_ALLOWED);
    }
}
