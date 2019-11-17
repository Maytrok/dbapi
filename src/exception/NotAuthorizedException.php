<?php

namespace dbapi\exception;

use dbapi\tools\HttpCode;
use Exception;

class NotAuthorizedException extends Exception
{
    public function __construct($message = null)
    {

        $message = $message ? $message : "Not Authorized";
        parent::__construct($message, HttpCode::UNAUTHORIZED);
    }
}
