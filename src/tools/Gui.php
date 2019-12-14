<?php

namespace dbapi\tools;

use dbapi\controller\ApiSimple;
use dbapi\db\Database;
use dbapi\exception\BadRequestException;
use dbapi\exception\NotAuthorizedException;
use dbapi\views\JsonView;
use Exception;
use PDO;

header("Access-Control-Allow-Origin: *");

class Gui
{

    public function run()
    {

        $api = new ApiSimple;





        $api->setPost(function ($bodyparams) {

            $view = new JsonView;



            $view = new JsonView;
            switch ($bodyparams['actions']) {

                case "dbs":

                    try {
                        Database::openConnection($bodyparams['user'], $bodyparams['password']);
                    } catch (Exception $th) {
                        throw new NotAuthorizedException("Wrong user or Password");
                    }


                    $sth = Database::getPDO()->prepare("show DATABASES ");
                    $sth->execute();

                    $view->setMainData($sth->fetchAll(PDO::FETCH_COLUMN));
                    return $view;



                case "table":
                    App::$DEBUG = true;
                    try {
                        Database::openConnection($bodyparams['user'], $bodyparams['password']);
                    } catch (Exception $th) {
                        throw new NotAuthorizedException("Wrong user or Password");
                    }

                    if (!isset($bodyparams['db'])) {
                        throw new BadRequestException("Param db missing");
                    }
                    $pdo = Database::getPDO();
                    $sth = $pdo->prepare("use " . $bodyparams['db']);
                    $sth->execute();

                    $sth = $pdo->prepare("show TABLES");
                    $sth->execute();

                    $view->setMainData($sth->fetchAll(PDO::FETCH_COLUMN));
                    return $view;



                case "valid":
                    try {
                        Database::openConnection($bodyparams['user'], $bodyparams['password']);
                    } catch (Exception $th) {
                        throw new NotAuthorizedException("Wrong user or Password");
                    }

                    return $view;
                case "create":
                    $view = new JsonView;
                    try {
                        Database::openConnection($bodyparams['user'], $bodyparams['password']);
                    } catch (Exception $th) {
                        throw new NotAuthorizedException("Wrong user or Password");
                    }

                    if (!isset($bodyparams['table']) || !isset($bodyparams['db'])) {
                        throw new BadRequestException("Param db or table missing");
                    }

                    $temp = new ClassGenerator($bodyparams['table'], $bodyparams['db']);

                    return $view;


                default:
                    throw new BadRequestException("Undefined Action:" . $bodyparams['actions']);
            }
        }, ["user", "password", "actions"]);


        $api->run();
    }
}
