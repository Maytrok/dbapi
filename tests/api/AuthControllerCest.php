<?php

class AuthControllerCest
{

    private $jwt;

    // tests
    public function tryToTest(ApiTester $i)
    {

        $i->sendGET("login.php");
        $i->seeResponseCodeIs("405");

        $i->sendPOST("login.php", ['username' => "admin", "password" => "admin"]);
        $i->seeResponseCodeIs("400");

        $i->sendPOST("login.php", ['user' => "admin", "password" => ""]);
        $i->seeResponseCodeIs("403");

        $i->sendPOST("login.php", ['user' => "admin", "password" => "admin"]);
        $i->seeResponseCodeIs("200");

        $i->seeResponseMatchesJsonType([
            'erfolg' => 'boolean',
            'jwt' => 'string',
            'status' => 'string'
        ]);

        list($jwt) = $i->grabDataFromResponseByJsonPath('$.jwt');
        $this->jwt = $jwt;
    }

    public function tryPatchMethod(ApiTester $i)
    {
        $i->sendPATCH("login.php");
        $i->seeResponseIsJson();
        $i->canSeeResponseCodeIs("405");


        $i->canSeeResponseContainsJson([
            "status" => "error",
            "error" => "PATCHRequest Method not Allowed",
            "exception" => "RequestMethodNotAllowedException"
        ]);
    }

    public function tryDeleteWithoutJWT(ApiTester $i)
    {
        $i->sendDELETE("login.php");
        $i->seeResponseIsJson();
        $i->seeResponseCodeIs("400");

        $i->canSeeResponseContainsJson([
            "status" => "error",
            "error" => "Error on Logout. Token was not submitted",
            "exception" => "Exception"
        ]);
    }

    public function tryDeleteWithoutWrongJWT(ApiTester $i)
    {
        $i->haveHttpHeader("JWT", "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyaWQiOiIxIiwiZXhwIjoxNTc2MTQzNTA2LCJpYXQiOjEzNTY5OTk1MjQsIm5iZiI6MTM1NzAwMDAwMH0.FdaegPK3w0Csc0DtLiJyEYAcYUnPTRrTN3OVqIj50CE");
        $i->sendDELETE("login.php");
        $i->seeResponseIsJson();
        $i->seeResponseCodeIs("404");

        $i->canSeeResponseContainsJson([
            "status" => "error",
            "error" => "User wurde nicht gefunden",
            "exception" => "NotFoundException"
        ]);
    }



    public function tryLogoutWithJWT(ApiTester $i)
    {
        $i->haveHttpHeader("JWT", $this->jwt);
        $i->sendDELETE("login.php");
        $i->seeResponseIsJson();
        $i->seeResponseCodeIs("200");

        $i->canSeeResponseContainsJson([
            "msg" => "successfully logged out",
            "status" => "Ok"
        ]);
    }
}
