<?php

namespace dbapi\exception;

use Exception;

class BadRequestException extends Exception
{
    public function __construct($message)
    {
        parent::__construct($message, 400);
    }
}
