<?php

namespace dbapi\interfaces;


interface Authenticate
{
    public static function getAuthUserId();
    public function login($user, $password);
    public function logout();
    public function authenticate();
}
