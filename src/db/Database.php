<?php

namespace dbapi\db;

use dbapi\exception\NotFoundException;
use dbapi\tools\App;
use PDO;
use Exception;
use dbapi\tools\EnvReader;
use dbapi\tools\Utils;
use PDOException;

class Database extends PDO
{

    private static $connection = null;

    public static $throwExceptionOnNotFound = false;

    private static $pdoOpt = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::MYSQL_ATTR_FOUND_ROWS => true];


    /**
     * @param string $user
     * @param string $password
     * @param string $server  localhost
     * @throws PDOException
     */
    public static function openConnection($user, $password, $server = "localhost")
    {

        self::$connection = new PDO("mysql:host=" . $server . ";charset=utf8", $user,  $password, self::$pdoOpt);
    }

    /**
     * @param EnvReader $reader
     * @throws PDOException
     */
    public static function open($reader)
    {
        $env = $reader == null ? new EnvReader() : $reader;
        $user = $env->env['USER'];
        $password = $env->env['PASSWORD'];
        $server = $env->env['SERVER'];

        self::$connection = new PDO("mysql:host=" . $server . ";charset=utf8", $user,  $password, self::$pdoOpt);
    }

    /**
     * @param EnvReader $reader
     * @throws PDOException
     * @return PDO
     */
    public static function getPDO($reader = null)
    {

        if (!self::$connection) {
            self::open($reader);
        }

        return self::$connection;
    }
    /**
     * Init mit ID
     * @param string $db
     * @param string $table
     * @param int $id
     * @throws NotFoundException
     * @return array
     */
    public static function get($db, $table, $id)
    {

        $sth = self::getPDO()->prepare("SELECT * from " . $db . "." . $table . " where id = :id");
        $sth->bindParam(":id", $id, PDO::PARAM_INT);
        $sth->execute();
        if (self::$throwExceptionOnNotFound) {
            if ($sth->rowCount() == 0) {
                throw new NotFoundException();
            }
        }
        $res = Utils::utf8encodeArray($sth->fetch());
        return $res;
    }

    /**
     * @param string $db
     * @param string $table
     * @param int $limit 0 = no limit
     * @param int $page 0 = no limit
     * @param array $column = [] all column will shown
     * @throws NotFoundException
     * @return array
     */
    public static function getAll($db, $table, $limit = 0, $page = 0, $column = [])
    {

        $query = "SELECT ";
        if (count($column) > 0) {
            $query .=  implode(", ", $column);
        } else {
            $query .= " *";
        }

        $query .= " from " . $db . "." . $table;


        $query .= self::handleLimit($limit, $page);


        $sth = self::getPDO()->prepare($query);
        $sth->execute();
        if (self::$throwExceptionOnNotFound) {
            if ($sth->rowCount() == 0) {
                throw new NotFoundException();
            }
        }
        $res = Utils::utf8encodeArray($sth->fetchAll());
        return $res;
    }

    /**
     * @param string $db Databasename
     * @param string $table Tablename
     * @param array $where 
     * @param int $limit 
     * @param int $page 
     * @throws Exception
     * @return array
     */
    public static function where($db, $table, $where, $limit = 0, $page = 0, $column = [])
    {

        $query = "SELECT ";
        if (count($column) > 0) {
            $query .=  implode(", ", $column);
        } else {
            $query .= " * ";
        }

        $query .= " from " . $db . "." . $table . " ";

        if (!is_array($where)) {
            App::$looger->critical("Where Param has to be an array");
            throw new Exception("Where Param has to be an array", 500);
        }

        $parms = [];
        if (count($where) > 0) {

            $query .= " where ";
            $qusub = "";

            foreach ($where as $key => $value) {

                if (is_array($value)) {
                    list($val, $mod) = $value;
                    $parms[] = $val;
                    $qusub .= "and " . $key . $mod . " ?";
                } else {
                    $parms[] = $value;
                    $qusub .= "and " . $key . "= ?";
                }
            }

            $query .= substr($qusub, 4);
        }

        $query .= self::handleLimit($limit, $page);

        App::$looger->debug("Database WHERE: " . $query);

        $sth = self::getPDO()->prepare($query);
        $sth->execute($parms);
        if (self::$throwExceptionOnNotFound) {
            if ($sth->rowCount() == 0) {
                throw new NotFoundException();
            }
        }
        return $sth->fetchAll();
    }

    /**
     * @param string $db Databasename
     * @param string $table Tablename
     * @param array $where
     */
    public static function countResults($db, $table, $where = null)
    {
        $query = "select count(*) count from " . $db . "." . $table;

        $parms = [];
        if ($where) {

            $query .= " where ";

            $qusub = "";


            foreach ($where as $key => $value) {
                $qusub .= "and " . $key . "= ?";
                $parms[] = $value;
            }

            $query .= substr($qusub, 4);
        }
        $sth = self::getPDO()->prepare($query);
        $sth->execute($parms);

        return $sth->fetch()['count'];
    }
    /**
     * @param string $db Databasename
     * @param string $table Tablename
     * @param array $arr
     * @throws PDOException
     */

    public static function create($db, $table, array $arr)
    {
        $query = "INSERT INTO  " . $db . "." . $table . " (";

        $keys = "";
        $values = "";

        foreach ($arr as $key => $prop) {

            if ($prop === null || $prop === "") {
                continue;
            }

            $keys  .= ",`$key`";
            $values .= ", :$key ";
        }

        $query .= substr($keys, 1) . " ) VALUES (" . substr($values, 1) . ")";


        $sth = self::getPDO()->prepare($query);
        foreach ($arr as $key => $prop) {

            if ($prop === null || $prop === "") {
                continue;
            }
            $sth->bindValue(":" . $key, $prop);
        }
        if ($sth->execute()) {
            App::$looger->info("Row created", ["query" => $query, "parms" => $arr]);
            return self::getPDO()->lastInsertId();
        } else {
            return false;
        }
    }

    /**
     * @param string $db Databasename
     * @param string $table Tablename
     * @param array $arr
     * @param int $id
     * @return bool
     */
    public static function update($db, $table, array $arr, $id = 0)
    {
        $query = "UPDATE " . $db . "." . $table .  " SET ";

        foreach ($arr as $key => $prop) {

            $query  .= "`$key`=";
            $query .= " :$key ,";
        }

        $query = substr($query, 0, strlen($query) - 1);
        $query .= " where id= :id";
        $sth = self::getPDO()->prepare($query);

        foreach ($arr as $key => $prop) {
            $sth->bindValue(":" . $key, $prop);
        }


        $sth->bindValue(":id", $id, PDO::PARAM_INT);

        if (!$sth->execute()) {
            return false;
        } else {
            if ($sth->rowCount() == 1) {
                App::$looger->info("Row update", ["query" => $query, "parms" => $arr, "id" => $id]);
                return true;
            } else {
                App::$looger->notice("update failed", ["query" => $query, "parms" => $arr, "id" => $id]);
                return false;
            }
        }
    }

    /**
     * @param string $db Databasename
     * @param string $table Tablename
     * @param int $id
     * @throws Exception
     * @return bool
     */
    public static function delete($db, $table, $id)
    {

        try {
            $query = "DELETE from " . $db . "." . $table . " where id = :id";
            self::getPDO()->beginTransaction();
            $sth = self::getPDO()->prepare($query);
            $sth->bindValue(":id", $id, PDO::PARAM_INT);
            if (!$sth->execute()) {
                App::$looger->error("Fatal Error at deleting this item", ["query" => $query, "id" => $id]);
                throw new Exception("Fatal Error at deleting this item", 500);
            }
            if ($sth->rowCount() != 1) {
                App::$looger->error("Fatal Error at deleting. To many results", ["query" => $query, "id" => $id]);
                throw new Exception("Fatal Error at deleting. To many results", 500);
            }
            self::getPDO()->commit();
            return true;
        } catch (\Exception $th) {

            self::getPDO()->rollBack();
            throw new Exception($th->getMessage(), 500);
        }
    }

    /**
     * @param int $limit Results per Page
     * @param int $page Number of the page
     * @return string
     */
    private static final function handleLimit($limit, $page)
    {

        if ($limit > 0 && $page > 0) {

            $offset = $page * $limit - $limit;
            $str = " LIMIT " . $limit;

            if ($offset) {
                $str .= " OFFSET " . $offset;
            }
            return $str;
        } else {
            return "";
        }
    }
}
