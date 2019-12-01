<?php

use dbapi\db\Database;
use dbapi\exception\NotFoundException;
use dbapi\tools\App;
use php\klassen\User;
use PHPUnit\Framework\TestCase;

class ModelBasicTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        $pdo = Database::getPDO();
        $query = "CREATE Database test_jwt";
        $pdo->exec($query);

        $pdo->exec("use test_jwt");
        $query = "CREATE TABLE IF NOT EXISTS `users` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `passwort` varchar(100) NOT NULL,
            `jwt` int(11) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
          )";
        $pdo->exec($query);


        include_once __DIR__ . "\\..\\..\\examples\\class\\basic\\UsersBasic.php";
        include_once __DIR__ . "\\..\\..\\examples\\class\\User.php";
    }

    public function tearDown()
    {
        Database::getPDO()->exec("truncate users");
    }


    public static function tearDownAfterClass()
    {
        $pdo = Database::getPDO()->exec("drop database test_jwt");
    }


    public function testCreateUserFailure()
    {

        $user = new User;

        $props = ["name" => "TestUser"];
        $user->setProperties($props);
        $this->expectException(Exception::class);
        $user->save();
    }

    public function testCreateUser()
    {

        $user = new User;

        $props = ["name" => "TestUser", 'passwort' => "1337", "jwt" => "test"];
        $user->setProperties($props);
        $this->assertNotEquals("0", $user->save());
    }

    public function testGetTestUser()
    {
        $user = new User;

        $props = ["name" => "TestUser", 'passwort' => "1337", "jwt" => "test"];
        $user->setProperties($props);
        $id = $user->save();

        $user = new User;
        $user->get($id);

        $this->assertEquals("TestUser", $user->getName());
        $this->assertNotEquals("1338", $user->getPasswort());
    }

    public function testUpdate()
    {

        $user = new User;

        $props = ["name" => "TestUser", 'passwort' => "1337", "jwt" => "test"];
        $user->setProperties($props);
        $id = $user->save();


        $user->setName("NewTestUser");
        $this->assertNotEquals($user->save(), "0");

        $this->assertNotEquals($user->getName(), "TestUser");
        $this->assertEquals($user->getName(), "NewTestUser");
    }

    public function testDeleteFailure()
    {
        $user = new User;


        $this->expectException(Exception::class);
        $user->delete();
    }

    public function testDelete()
    {

        $user = new User;
        $props = ["name" => "TestUser", 'passwort' => "1337", "jwt" => "test"];
        $user->setProperties($props);
        $id = $user->save();

        $user = new User();
        $user->get($id);

        $this->assertTrue($user->delete());
    }

    public function testWhere()
    {
        $user = new User;
        $props = ["name" => "TestWhereUser", 'passwort' => "1337", "jwt" => "test"];
        $user->setProperties($props);
        $id = $user->save();

        $user = new User();

        $this->assertTrue($user->where(["name" => "TestWhereUser"]));

        $user = new User();
        $this->expectException(NotFoundException::class);
        $user->where(["name" => "TestWhereUser1"]);
    }
}
