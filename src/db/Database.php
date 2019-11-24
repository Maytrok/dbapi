<?php

namespace dbapi\db;

use dbapi\exception\NotFoundException;
use PDO;
use Exception;
use dbapi\tools\EnvReader;
use dbapi\tools\HttpCode;
use dbapi\tools\Utils;
use PDOException;

class Database extends PDO
{

    private static $connection = null;

    public static $throwExceptionOnNotFound = false;


    /**
     * @param string $user
     * @param string $password
     * @param string $server  localhost
     * @throws PDOException
     */
    public static function openConnection($user, $password, $server = "localhost")
    {
        $opt = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
        self::$connection = new PDO("mysql:host=" . $server . ";charset=utf8", $user,  $password, $opt);
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
        $opt = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
        self::$connection = new PDO("mysql:host=" . $server . ";charset=utf8", $user,  $password, $opt);
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
     * @throws NotFoundException
     * @return array
     */
    public static function getAll($db, $table, $limit = null, $page = null)
    {
        $query = "SELECT * from " . $db . "." . $table;

        if ($limit && $page) {
            $query .= self::handleLimit($limit, $page);
        }

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
    public static function where($db, $table, $where, $limit = 0, $page = 0)
    {

        $query = "SELECT * from " . $db . "." . $table . " where ";

        if (!is_array($where)) {
            throw new Exception("Where Param has to be an array", HttpCode::INTERNAL_SERVER_ERROR);
        }

        $parms = [];
        $qusub = "";

        foreach ($where as $key => $value) {
            $qusub .= "and " . $key . "= ?";
            $parms[] = $value;
        }

        $query .= substr($qusub, 4);


        if ($limit > 0 && $page > 0) {
            $query .= self::handleLimit($limit, $page);
        }

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
     * @return bool|int
     */
    public static function update($db, $table, array $arr, $id)
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
            return $sth->rowCount();
        }
    }

    /**
     * @param string $db Databasename
     * @param string $table Tablename
     * @param int $id
     * @throws Exception
     * @return bool|int
     */
    public static function delete($db, $table, $id)
    {

        try {
            $query = "DELETE from " . $db . "." . $table . " where id = :id";
            self::getPDO()->beginTransaction();
            $sth = self::getPDO()->prepare($query);
            $sth->bindValue(":id", $id, PDO::PARAM_INT);
            if (!$sth->execute()) {
                throw new Exception("Fatal Error at deleting this item", HttpCode::INTERNAL_SERVER_ERROR);
            }
            if ($sth->rowCount() != 1) {
                throw new Exception("Fatal Error at deleting. To many results", HttpCode::INTERNAL_SERVER_ERROR);
            }
            self::getPDO()->commit();
            return true;
        } catch (\Throwable $th) {

            self::getPDO()->rollBack();
            throw new Exception($th->getMessage(), HttpCode::INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param int $limit Results per Page
     * @param int $page Number of the page
     * @return string
     */
    private static final function handleLimit($limit, $page)
    {

        $offset = $page * $limit - $limit;
        $str = " LIMIT " . $limit;

        if ($offset) {
            $str .= " OFFSET " . $offset;
        }
        return $str;
    }
}
