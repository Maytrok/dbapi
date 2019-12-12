<?php

use dbapi\db\Database;

class SimpleCest
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


    public function tryBrokenGet(ApiTester $i)
    {

        $i->sendGET("brokenget.php");
        $i->seeResponseIsJson();
        $i->canSeeResponseCodeIsServerError();
        $i->canSeeResponseContainsJson([
            "status" => "error",
            "error" => "requiredParams must be an array",
            "exception" => "LoggerExceptionCrit"
        ]);
    }

    public function tryBrokenGetView(ApiTester $i)
    {

        $i->sendGET("brokengetview.php", ["myparm" => 1]);
        $i->seeResponseIsJson();
        $i->canSeeResponseCodeIsServerError();
        $i->canSeeResponseContainsJson([
            "status" => "error",
            "error" => "Hooks has to return an View",
            "exception" => "LoggerExceptionCrit"
        ]);
    }


    public function tryBrokenPost(ApiTester $i)
    {

        $i->sendDELETE("brokenpost.php");
        $i->seeResponseIsJson();
        $i->canSeeResponseCodeIsServerError();
        $i->canSeeResponseContainsJson([
            "status" => "error",
            "error" => "Argument must be an function",
            "exception" => "LoggerExceptionCrit"
        ]);
    }

    public function trySimpleGetWithoutReqiredParams(ApiTester $i)
    {

        $i->sendGET("simple.php");
        $i->seeResponseIsJson();
        $i->canSeeResponseCodeIsClientError();
        $i->canSeeResponseContainsJson([
            "status" => "error",
            "error" => "Required Param myparm missing",
            "exception" => "BadRequestException"
        ]);
    }


    public function trySimpleGet(ApiTester $i)
    {

        $i->sendGET("simple.php", ["myparm" => 1]);
        $i->seeResponseIsJson();
        $i->canSeeResponseCodeIs(200);
        $i->canSeeResponseContainsJson([
            "msg" => "Hello there!"
        ]);
    }

    public function trySimpleNotImplementedPost(ApiTester $i)
    {

        $i->sendPOST("simple.php");
        $i->seeResponseIsJson();
        $i->canSeeResponseCodeIs(405);
        $i->canSeeResponseContainsJson([
            "status" => "error",
            "error" => "POSTRequest Method not Allowed",
            "exception" => "RequestMethodNotAllowedException"
        ]);
    }
}
