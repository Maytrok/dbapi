<?php

namespace dbapi\exception;

use Exception;

class NoValidSessionException extends Exception
{
    public function __construct($message)
    {
        parent::__construct($message, 401);
    }
}
