<?php

use Codeception\Test\Unit;
use dbapi\db\Database;
use dbapi\exception\NotFoundException;
use php\klassen\User;

class ModelBasicTest extends Unit
{

    protected function _after()
    {
        Database::getPDO()->exec("truncate users");
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
        $user->save();

        $user = new User();

        $this->assertTrue($user->where(["name" => "TestWhereUser"]));

        $user = new User();
        $this->expectException(NotFoundException::class);
        $user->where(["name" => "TestWhereUser1"]);
    }

    public function testWhereAdv()
    {
        $user = new User;
        $props = ["name" => "TestWhereUser", 'passwort' => "1337", "jwt" => "test"];
        $user->setProperties($props);
        $user->save();

        $user = clone $user;
        $user->setName("Another User");
        $user->setPasswort("0815");
        $user->save();

        $user = new User;
        $user->where(["name" => ["Another User", "="]]);
        $this->assertEquals("0815", $user->getPasswort());

        $user = new User;
        $user->where(["name" => ["Another User", "!="]]);
        $this->assertEquals("1337", $user->getPasswort());
        $this->assertNotEquals("13378", $user->getPasswort());


        $user = new User;
        $this->expectException(\Exception::class);
        $user->where(["name" => ["Test", "!="]]);
    }

    public function testStaticFind()
    {
        $user = new User;

        $props = ["name" => "TestUser", 'passwort' => "1337", "jwt" => "test"];
        $user->setProperties($props);
        $id = $user->save();


        $model = User::find($id);
        $this->assertEquals("TestUser", $model->getName());
        $this->assertNotEquals("TestUser1", $model->getName());
    }

    public function testStaticFindOrFail()
    {
        $user = new User;

        $props = ["name" => "TestUser", 'passwort' => "1337", "jwt" => "test"];
        $user->setProperties($props);
        $id = $user->save();


        $model = User::findOrFail($id);
        $this->assertEquals("TestUser", $model->getName());

        $this->expectException(NotFoundException::class);
        $model = User::findOrFail(999);
    }

    public function testStaticAll()
    {

        $user = new User;

        $props = ["name" => "TestUser", 'passwort' => "1337", "jwt" => "test"];
        $user->setProperties($props);
        $user->save();

        for ($i = 0; $i < 10; $i++) {
            $clone = clone $user;
            $clone->save();
        }
        $res = User::all();
        $this->assertIsArray($res);
        $this->assertCount(11, $res);
    }

    public function testStaticFindWhere()
    {
        $user = new User;

        $props = ["name" => "TestUser", 'passwort' => "1337", "jwt" => "test"];
        $user->setProperties($props);

        for ($i = 1; $i < 11; $i++) {

            $user = clone $user;

            if ($i % 3 == 0) {
                $user->setJwt("mod");
            } else {
                $user->setJwt("test");
            }

            $user->save();
        }

        $res = User::findWhere("name", "TestUser");


        $this->assertIsArray($res);
        $this->assertCount(10, $res);
        $this->assertNotCount(11, $res);

        $res = User::findWhere("jwt", "mod", "!=");

        $this->assertIsArray($res);
        $this->assertCount(7, $res);
        $this->assertNotCount(3, $res);
    }
}
