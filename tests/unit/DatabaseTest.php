<?php

use dbapi\db\Database;
use dbapi\exception\NotFoundException;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{


    public static function setUpBeforeClass()
    {
        $pdo = Database::getPDO();
        $query = "CREATE Database test_jwt";
        $pdo->exec($query);

        $pdo->exec("use test_jwt");
        $query = "CREATE TABLE IF NOT EXISTS `content` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `content` text NOT NULL,
            `user` int NOT NULL,
            PRIMARY KEY (`id`)
          )";
        $pdo->exec($query);
    }


    public function tearDown()
    {
        Database::getPDO()->exec("truncate content");
    }

    public static function tearDownAfterClass()
    {
        Database::getPDO()->exec("drop database test_jwt");
    }

    public function testDBConnected()
    {

        $sth = Database::getPDO()->prepare("select * from content");
        $this->assertTrue($sth->execute());
    }

    public function testInsert()
    {

        $this->assertIsString(Database::create("test_jwt", "content", ["content" => "Lorem ipsum dolor, sit amet consectetur adipisicing elit. Provident corporis necessitatibus architecto reprehenderit unde ipsum odio eveniet. Mollitia quas temporibus reiciendis est laboriosam vel, officia amet quidem beatae eligendi dolores.", "user" => 1]));
    }

    public function testInsertFailureWhileToMuchArgumentsParsed()
    {
        $this->expectException(PDOException::class);
        Database::create("test_jwt", "content", ["content" => "Lorem ipsum dolor, sit amet consectetur adipisicing elit. Provident corporis necessitatibus architecto reprehenderit unde ipsum odio eveniet. Mollitia quas temporibus reiciendis est laboriosam vel, officia amet quidem beatae eligendi dolores.", "user" => 1, "test" => "fail"]);
    }

    public function testInsertFailureMissingProp()
    {
        $this->expectException(PDOException::class);
        Database::create("test_jwt", "content", ["content" => "Lorem ipsum dolor, sit amet consectetur adipisicing elit. Provident corporis necessitatibus architecto reprehenderit unde ipsum odio eveniet. Mollitia quas temporibus reiciendis est laboriosam vel, officia amet quidem beatae eligendi dolores.", "test" => "fail"]);
    }

    public function testInsertFailureDuplicateEntry()
    {
        $id = Database::create("test_jwt", "content", ["content" => "Lorem ipsum dolor, sit amet consectetur adipisicing elit. Provident corporis necessitatibus architecto reprehenderit unde ipsum odio eveniet. Mollitia quas temporibus reiciendis est laboriosam vel, officia amet quidem beatae eligendi dolores.", "user" => 1]);

        $this->expectException(PDOException::class);
        $this->assertIsString(Database::create("test_jwt", "content", ["content" => "Lorem ipsum dolor, sit amet consectetur adipisicing elit. Provident corporis necessitatibus architecto reprehenderit unde ipsum odio eveniet. Mollitia quas temporibus reiciendis est laboriosam vel, officia amet quidem beatae eligendi dolores.", "user" => 1, "id" => $id]));
    }

    public function testCount()
    {

        $c = ["content" => "Lorem ipsum dolor, sit amet consectetur adipisicing elit. Provident corporis necessitatibus architecto reprehenderit unde ipsum odio eveniet. Mollitia quas temporibus reiciendis est laboriosam vel, officia amet quidem beatae eligendi dolores.", "user" => 3];

        for ($i = 0; $i < 4; $i++) {
            Database::create("test_jwt", "content", $c);
        }

        $this->assertEquals(Database::countResults("test_jwt", "content", ["user" => 3]), "4");
    }

    public function testUpdate()
    {

        $c = ["content" => "Lorem ipsum dolor, sit amet consectetur adipisicing elit. Provident corporis necessitatibus architecto reprehenderit unde ipsum odio eveniet. Mollitia quas temporibus reiciendis est laboriosam vel, officia amet quidem beatae eligendi dolores.", "user" => 5];


        $this->assertIsString($id = Database::create("test_jwt", "content", $c));

        $this->assertTrue(Database::update("test_jwt", "content", ["user" => 6], $id));
        $this->assertFalse(Database::update("test_jwt", "content", ["user" => 5], 500));
    }

    public function testDelete()
    {
        $c = ["content" => "Lorem ipsum dolor, sit amet consectetur adipisicing elit. Provident corporis necessitatibus architecto reprehenderit unde ipsum odio eveniet. Mollitia quas temporibus reiciendis est laboriosam vel, officia amet quidem beatae eligendi dolores.", "user" => 10];

        $id = Database::create("test_jwt", "content", $c);

        $this->assertTrue(Database::delete("test_jwt", "content", $id));

        $this->expectException(Exception::class);
        Database::delete("test_jwt", "content", $id);
    }


    public function testWhere()
    {

        $c = ["content" => "Lorem ipsum dolor, sit amet consectetur adipisicing elit. Provident corporis necessitatibus architecto reprehenderit unde ipsum odio eveniet. Mollitia quas temporibus reiciendis est laboriosam vel, officia amet quidem beatae eligendi dolores.", "user" => 20];

        for ($i = 0; $i < 10; $i++) {
            Database::create("test_jwt", "content", $c);
        }

        $res = Database::where("test_jwt", "content", ["user" => 20]);

        $this->assertIsArray($res);
        $this->assertCount(10, $res);

        $res = Database::where("test_jwt", "content", ["user" => 21]);
        $this->assertIsArray($res);
        $this->assertCount(0, $res);
    }

    public function testGet()
    {
        $c = ["content" => "Lorem ipsum dolor, sit amet consectetur adipisicing elit. Provident corporis necessitatibus architecto reprehenderit unde ipsum odio eveniet. Mollitia quas temporibus reiciendis est laboriosam vel, officia amet quidem beatae eligendi dolores.", "user" => 22];
        $id = Database::create("test_jwt", "content", $c);

        $res = Database::get("test_jwt", "content", $id);

        $this->assertIsArray($res);
        $this->assertCount(3, $res);
        $this->assertArrayHasKey("content", $res);
    }

    public function testGetAll()
    {
        $c = ["content" => "Lorem ipsum dolor, sit amet consectetur adipisicing elit. Provident corporis necessitatibus architecto reprehenderit unde ipsum odio eveniet. Mollitia quas temporibus reiciendis est laboriosam vel, officia amet quidem beatae eligendi dolores.", "user" => 23];

        for ($i = 0; $i < 20; $i++) {
            Database::create("test_jwt", "content", $c);
        }

        $res = Database::getAll("test_jwt", "content");
        $this->assertIsArray($res);
        $this->assertCount(20, $res);

        $res = Database::getAll("test_jwt", "content", 7, 3);
        $this->assertCount(6, $res);

        $res = Database::getAll("test_jwt", "content", 7, 4);
        $this->assertCount(0, $res);
    }
}
