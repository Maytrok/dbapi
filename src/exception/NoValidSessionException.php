<?php

namespace dbapi\exception;

use dbapi\tools\HttpCode;
use Exception;

class NoValidSessionException extends Exception
{
    public function __construct($message)
    {
        parent::__construct($message, HttpCode::UNAUTHORIZED);
    }
}
