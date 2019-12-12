<?php

use dbapi\db\Database;

class RestApiCest
{

    private $jwt;
    public function beforeStep(ApiTester $i)
    {

        Database::openConnection("root", "");
        Database::getPDO()->exec("TRUNCATE jwt.content");

        $i->sendPOST("login.php", ['user' => "admin", "password" => "admin"]);
        list($jwt) = $i->grabDataFromResponseByJsonPath('$.jwt');

        $this->jwt = $jwt;
    }

    // // tests
    public function withoutJWT(ApiTester $i)
    {

        $i->sendGET("content.php");
        $i->seeResponseIsJson();
        $i->canSeeResponseCodeIs(401);

        $i->seeResponseContainsJson([
            'error' => "No JWT Header was submitted"
        ]);
    }

    public function withJWT(ApiTester $i)
    {

        $i->haveHttpHeader("JWT", $this->jwt);
        $i->haveHttpHeader('content-type', 'application/json');
        $i->sendGET("content.php");
        $i->seeResponseIsJson();
        $i->canSeeResponseCodeIs(404);
    }

    public function postTest(ApiTester $i)
    {

        $i->haveHttpHeader("JWT", $this->jwt);
        $i->sendPOST("content.php", ["user" => "1"]);
        $i->canSeeResponseCodeIs(400);
        $i->seeResponseContainsJson([
            'status' => 'error',
            "exception" => "BadRequestException"
        ]);

        $i->sendPOST("content.php", ["user" => "1", "content" => "FIRST ENTRY"]);
        $i->canSeeResponseCodeIs(201);
        $i->seeResponseContainsJson([
            'data' => [
                "id" => 1,
                "content" => "FIRST ENTRY",
                "user" => 1
            ]
        ]);

        $i->sendPOST("content.php", ["user" => "1", "content" => "SECOND ENTRY"]);
        $i->sendPOST("content.php", ["user" => "1", "content" => "THIRD ENTRY"]);
        $i->sendPOST("content.php", ["user" => "1", "content" => "FOURTH ENTRY"]);
    }

    public function getTest(ApiTester $i)
    {
        $i->haveHttpHeader("JWT", $this->jwt);
        $i->sendGET("content.php");
        $i->seeResponseIsJson();
        $i->seeResponseJsonMatchesJsonPath('$.data[1]');
    }

    public function testPagination(ApiTester $i)
    {

        $i->haveHttpHeader("JWT", $this->jwt);
        $i->sendGET("content.php", ["page" => 1, "per_page" => 2]);
        $i->seeResponseIsJson();
        $i->seeResponseCodeIs(200);
        $i->cantSeeResponseJsonMatchesJsonPath("$.data[2]");
        $i->canSeeResponseJsonMatchesJsonPath("$.data[1]");

        $i->canSeeResponseContainsJson([
            "pages" => 2,
            "status" => "Ok",
            "count" => 2
        ]);
    }

    public function tryCustomRequestField(ApiTester $i)
    {
        $i->haveHttpHeader("JWT", $this->jwt);
        $i->sendGET("content.php", ["content" => "FIRST ENTRY!"]);
        $i->seeResponseIsJson();
        $i->seeResponseCodeIs(404);

        $i->sendGET("content.php", ["content" => "FIRST ENTRY"]);
        $i->seeResponseIsJson();
        $i->seeResponseCodeIs(200);
        $i->cantSeeResponseJsonMatchesJsonPath("$.data[1]");
        $i->canSeeResponseJsonMatchesJsonPath("$.data[0]");
    }

    public function tryCountResult(ApiTester $i)
    {
        $i->haveHttpHeader("JWT", $this->jwt);
        $i->sendGET("content.php", ["count" => ""]);
        $i->seeResponseIsJson();
        $i->seeResponseCodeIs(200);

        $i->canSeeResponseContainsJson([
            "count" => "4",
            "status" => "Ok"
        ]);
    }


    public function getSingleTest(ApiTester $i)
    {
        $i->haveHttpHeader("JWT", $this->jwt);
        $i->sendGET("content.php", ["id" => 2]);
        $i->seeResponseIsJson();
        $i->seeResponseContainsJson([
            'data' => [
                "id" => 2,
                "content" => "SECOND ENTRY",
                "user" => 1
            ]
        ]);
    }

    public function getSpecialFormatTest(ApiTester $i)
    {

        $i->haveHttpHeader("JWT", $this->jwt);
        $i->sendGET("content.php", ["special_format" => "user,content"]);
        $i->seeResponseIsJson();
        $i->seeResponseCodeIs(200);

        $i->cantSeeResponseJsonMatchesJsonPath("$.data[0].id");
        $i->canSeeResponseJsonMatchesJsonPath("$.data[0].content");

        $i->sendGET("content.php", ["special_format" => "user,content,wrongparam"]);
        $i->seeResponseIsJson();
        $i->seeResponseCodeIs(400);
        $i->seeResponseContainsJson([
            "status" => "error",
            "error" => "Wrong format request",
            "exception" => "BadRequestException"
        ]);
    }



    public function getSingleFailureTest(ApiTester $i)
    {
        $i->haveHttpHeader("JWT", $this->jwt);
        $i->sendGET("content.php", ["id" => 6]);
        $i->seeResponseIsJson();
        $i->canSeeResponseCodeIs(404);

        $i->sendGET("content.php", ["id" => "nyx"]);
        $i->seeResponseIsJson();
        $i->canSeeResponseCodeIs(400);
    }




    public function patchFailureTest(ApiTester $i)
    {
        $i->haveHttpHeader("JWT", $this->jwt);
        $i->sendPATCH("content.php", ["content" => "NEW EDIT"]);
        $i->seeResponseIsJson();
        $i->canSeeResponseCodeIs(400);

        $i->seeResponseContainsJson([
            'error' => "No ID submitted"
        ]);

        $i->sendPATCH("content.php?id=7", ["content" => "NEW EDIT"]);
        $i->seeResponseIsJson();
        $i->canSeeResponseCodeIs(404);

        $i->seeResponseContainsJson([
            'error' => "Ressource not found"
        ]);
    }

    public function patchTest(ApiTester $i)
    {
        $i->haveHttpHeader("JWT", $this->jwt);
        $i->sendPATCH("content.php?id=2", ["content" => "NEW EDIT"]);
        $i->seeResponseIsJson();
        $i->canSeeResponseCodeIs(200);


        $i->sendGET("content.php?id=2");
        $i->seeResponseIsJson();
        $i->canSeeResponseCodeIs(200);
        $i->seeResponseContainsJson([
            'data' => [
                "content" => "NEW EDIT"
            ]
        ]);
    }

    public function deleteTest(ApiTester $i)
    {

        $i->haveHttpHeader("JWT", $this->jwt);
        $i->sendDELETE("content.php?id=2");
        $i->seeResponseIsJson();
        $i->canSeeResponseCodeIs(200);
        $i->seeResponseContainsJson([
            'id' => "2"
        ]);
    }
}
