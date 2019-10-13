<?php

namespace dbapi\interfaces;


interface Authenticate
{
    public static function getAuthUserId();
    public static function getModel();
    public function login($user, $password);
    public function logout();
    public function authenticate();
}
