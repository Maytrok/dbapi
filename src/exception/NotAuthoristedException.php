<?php

namespace dbapi\exception;

use Exception;

class NotAuthorizedException extends Exception
{
    public function __construct($message = null)
    {

        $message = $message ? $message : "Not Authorised";
        parent::__construct($message, 403);
    }
}
