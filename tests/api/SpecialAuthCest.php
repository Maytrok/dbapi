<?php

use dbapi\db\Database;

class SpecialAuthCest
{

    private $jwt;
    public function beforeStep(ApiTester $i)
    {
        $i->sendPOST("login.php", ['user' => "admin", "password" => "admin"]);
        list($jwt) = $i->grabDataFromResponseByJsonPath('$.jwt');

        $this->jwt = $jwt;

        Database::openConnection("root", "");
        Database::getPDO()->exec("TRUNCATE jwt.content");


        for ($i = 0; $i < 10; $i++) {

            $str = $i . " Content";
            Database::create("jwt", "content", ["content" => $str, "user" => 1]);
        }
    }

    public function tryGetWithoutJWT(ApiTester $i)
    {
        $i->sendGET("auth.php");
        $i->seeResponseIsJson();
        $i->canSeeResponseCodeIs(401);
        $i->canSeeResponseContainsJson([
            "error" => "No JWT Header was submitted",
            "exception" => "NoValidSessionException"
        ]);
    }

    public function tryGetMethod(ApiTester $i)
    {
        $i->haveHttpHeader("JWT", $this->jwt);
        $i->sendGET("auth.php");
        $i->seeResponseIsJson();
        $i->canSeeResponseCodeIs(200);
        $i->canSeeResponseJsonMatchesJsonPath("$.data[3]");
    }

    public function tryPostMethodThatIsNotAllowed(ApiTester $i)
    {
        $i->haveHttpHeader("JWT", $this->jwt);
        $i->sendPOST("auth.php");
        $i->seeResponseIsJson();
        $i->canSeeResponseCodeIs(401);
        $i->canSeeResponseContainsJson([
            "status" => "error",
            "error" => "Not Authorized",
            "exception" => "NotAuthorizedException"
        ]);
    }
}
