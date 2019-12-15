<?php

use Codeception\Util\Xml;
use dbapi\db\Database;
use Faker\Factory;

class XmlCest
{
    public function beforeStep(ApiTester $i)
    {
        $i->sendPOST("login.php", ['user' => "admin", "password" => "admin"]);
        list($jwt) = $i->grabDataFromResponseByJsonPath('$.jwt');

        $this->jwt = $jwt;

        Database::openConnection("root", "");
        Database::getPDO()->exec("TRUNCATE jwt.content");

        $faker = Factory::create();


        for ($i = 0; $i < 10; $i++) {
            Database::create("jwt", "content", ["content" => $faker->sentence(10), "user" => $faker->randomDigitNotNull]);
        }
    }


    public function testSimpleXml(ApiTester $i)
    {

        $i->sendGET("xml.php");
        $i->canSeeResponseCodeIs(200);
        $i->seeResponseIsXml();

        $i->seeXmlResponseMatchesXpath("//root/data/item[10]");
        $i->cantseeXmlResponseMatchesXpath("//root/data/item[11]");
    }


    public function testXmlException(ApiTester $i)
    {

        $i->sendGET("xml.php?special_get=exception");
        $i->canSeeResponseCodeIs(400);
        $i->seeResponseIsXml();

        $i->seeXmlResponseMatchesXpath("//root/error");
        $i->cantseeXmlResponseMatchesXpath("//root/data/item[11]");
    }

    public function testPost(ApiTester $i)
    {
        $i->sendPOST("xml.php", [
            "user" => 1,
            "content" => "testcontent"
        ]);

        $i->canSeeResponseCodeIs(201);
        $i->seeResponseIsXml();
        $i->cantSeeXmlResponseMatchesXpath("//root/error");
        $i->canSeeXmlResponseMatchesXpath("//root/data/id");
    }

    public function testPatch(ApiTester $i)
    {
        $i->sendPATCH("xml.php?id=11", [
            "user" => 2,
        ]);

        $i->canSeeResponseCodeIs(200);
        $i->seeResponseIsXml();
        $i->cantSeeXmlResponseMatchesXpath("//root/error");
        $i->canSeeXmlResponseMatchesXpath("//root/data/id");
    }


    public function testDelete(ApiTester $i)
    {
        $i->sendDELETE("xml.php?id=11");

        $i->canSeeResponseCodeIs(200);
        $i->seeResponseIsXml();
        $i->cantSeeXmlResponseMatchesXpath("//root/error");
        $i->canSeeXmlResponseMatchesXpath("//root/id");
        $i->canSeeXmlResponseMatchesXpath("//root/status");
    }
}
