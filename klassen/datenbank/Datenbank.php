<?php

namespace Vendor\Dbapi\Klassen\Datenbank;

use PDO;
use Exception;
use Vendor\dbapi\Klassen\Tools\EnvReader;

class Datenbank extends PDO
{

    private static $connection = null;

    /**
     * @param EnvReader $reader
     * @return PDO
     */
    public static function getPDO($reader = null)
    {

        if (!self::$connection) {

            $env = $reader == null ? new EnvReader() : $reader;
            $user = $env->env['USER'];
            $password = $env->env['PASSWORD'];
            $server = $env->env['SERVER'];
            $opt = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
            self::$connection = new PDO("mysql:host=" . $server . ";charset=utf8", $user,  $password, $opt);
        }

        return self::$connection;
    }
    /**
     * Init mit ID
     * @param int $id
     * @return array
     */
    public static function get($db, $table, $id)
    {

        $sth = self::getPDO()->prepare("SELECT * from " . $db . "." . $table . " where id = :id");
        $sth->bindParam(":id", $id, PDO::PARAM_INT);
        $sth->execute();
        if ($sth->rowCount() == 0) {
            throw new Exception("Requested item not found", 404);
        }
        $res = utf8encodeArray($sth->fetch());
        return $res;
    }

    public static function getAll($db, $table)
    {
        $sth = self::getPDO()->prepare("SELECT * from " . $db . "." . $table);
        $sth->execute();
        if ($sth->rowCount() == 0) {
            throw new Exception("Table not found or empty", 404);
        }
        $res = utf8encodeArray($sth->fetchAll());
        return $res;
    }

    public static function create($db, $table, array $arr)
    {
        $query = "INSERT INTO  " . $db . "." . $table . " (";

        $keys = "";
        $values = "";

        foreach ($arr as $key => $prop) {

            $keys  .= ",`$key`";
            $values .= ", :$key ";
        }

        $query .= substr($keys, 1) . " ) VALUES (" . substr($values, 1) . ")";


        $sth = self::getPDO()->prepare($query);
        foreach ($arr as $key => $prop) {
            $sth->bindValue(":" . $key, $prop);
        }
        if ($sth->execute()) {
            return self::getPDO()->lastInsertId();
        } else {
            return false;
        }
    }

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

    public static function delete($db, $table, $id)
    {

        try {
            $query = "DELETE from " . $db . "." . $table . " where id = :id";
            self::getPDO()->beginTransaction();
            $sth = self::getPDO()->prepare($query);
            $sth->bindValue(":id", $id, PDO::PARAM_INT);
            if (!$sth->execute()) {
                throw new Exception("Fataler Fehler beim loeschen des Items");
            }
            if ($sth->rowCount() != 1) {
                throw new Exception("Fataler Fehler beim loeschen des Items: Der Count ist ungueltig");
            }
            self::getPDO()->commit();
            return true;
        } catch (\Throwable $th) {

            self::getPDO()->rollBack();
            throw new Exception($th->getMessage(), 500);
        }
    }
}
