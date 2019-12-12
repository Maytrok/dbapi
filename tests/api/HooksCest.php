<?php

use dbapi\db\Database;

class HooksCest
{
    public function beforeStep(ApiTester $i)
    {
        Database::openConnection("root", "");
        Database::getPDO()->exec("TRUNCATE jwt.content");


        for ($i = 0; $i < 10; $i++) {

            $str = $i . " Content";
            Database::create("jwt", "content", ["content" => $str, "user" => 1]);
        }
    }


    public function tryDisallowedMethodDelete(ApiTester $i)
    {

        $i->sendDELETE("hooks.php?id=1");
        $i->canSeeResponseIsJson();
        $i->canSeeResponseCodeIs("405");
        $i->seeResponseContainsJson([
            "status" => "error",
            "error" => "Request Method not Allowed",
            "exception" => "Exception"
        ]);
    }

    public function tryGetHook(ApiTester $i)
    {
        $i->sendGET("hooks.php");
        $i->canSeeResponseIsJson();
        $i->canSeeResponseCodeIs("200");

        $i->seeResponseContainsJson([
            "greetings" => "Hello there"
        ]);
    }

    public function trySpecialGet(ApiTester $i)
    {
        $i->sendGET("hooks.php", ["special_get" => "latest"]);
        $i->canSeeResponseIsJson();
        $i->canSeeResponseCodeIs("200");
        $i->canSeeResponseJsonMatchesJsonPath("$.data[2]");
        $i->cantSeeResponseJsonMatchesJsonPath("$.data[3]");
    }

    public function tryWrongSpecialGet(ApiTester $i)
    {
        $i->sendGET("hooks.php", ["special_get" => "wrong"]);
        $i->canSeeResponseIsJson();
        $i->canSeeResponseCodeIs("500");
        $i->seeResponseContainsJson([
            "status" => "error",
            "error" => "Server error. Special Get Method has to returned the given view",
            "exception" => "LoggerExceptionCrit"
        ]);
    }
}
