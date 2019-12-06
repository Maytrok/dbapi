<?php

namespace dbapi\exception;

use Exception;

class NotFoundException extends Exception
{
    public function __construct($message = null)
    {
        $message = $message ? $message : "Item not found";
        parent::__construct($message, 404);
    }
}
