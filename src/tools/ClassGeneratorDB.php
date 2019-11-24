<?php

namespace dbapi\tools;

use dbapi\db\Database;

class ClassGeneratorDB
{

    public function __construct($db, $config = [])
    {
        Database::getPDO()->exec("use " . $db);
        foreach (Database::getPDO()->query("SHOW TABLES") as $value) {

            $res = new ClassGenerator($value['Tables_in_' . $db], $db, $config);
        }
    }
}
