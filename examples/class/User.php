<?php

namespace php\klassen;


use php\klassen\basic\UsersBasic;


class User extends UsersBasic
{
    protected function noRessourceFound()
    {
        return "User wurde nicht gefunden";
    }

    protected function getJWTKeySecret()
    {
        return "example_key";
    }
}
