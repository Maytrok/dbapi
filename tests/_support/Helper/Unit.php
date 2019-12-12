<?php

namespace Helper;

use dbapi\db\Database;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Unit extends \Codeception\Module
{

    public function _initialize()
    {

        Database::openConnection("root", "");
        $pdo = Database::getPDO();
        $pdo->exec("DROP DATABASE if exists test_jwt");

        $pdo->exec("CREATE Database test_jwt");
        $pdo->exec("use test_jwt");

        $query = "CREATE TABLE `content` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `content` text NOT NULL,
            `user` int NOT NULL,
            PRIMARY KEY (`id`)
          )";
        Database::getPDO()->exec($query);


        $pdo = Database::getPDO();
        $query = "CREATE TABLE IF NOT EXISTS `users` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `passwort` varchar(100) NOT NULL,
            `jwt` varchar(100) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
          )";
        $pdo->exec($query);

        include_once __DIR__ . "\\..\\..\\..\\examples\\class\\basic\\UsersBasic.php";
        include_once __DIR__ . "\\..\\..\\..\\examples\\class\\User.php";
    }
}
