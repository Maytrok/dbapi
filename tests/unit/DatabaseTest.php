<?php

use Codeception\Test\Unit;
use dbapi\db\Database;

class DatabaseTest extends Unit
{

    protected function _after()
    {
        Database::getPDO()->exec("truncate content");
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
