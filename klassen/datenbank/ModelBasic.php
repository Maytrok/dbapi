<?php

namespace Vendor\Dbapi\Klassen\Datenbank;


use Exception;
use Vendor\Dbapi\Interfaces\ModelProps;

/**
 * public static function getTableName();
 * public static function getDB();
 * /**
 * @return array
 * array_keys(get_class_vars(self::class))
 * Es werden alle Properties fuer die Klasse definiert 
 * protected function getProperties();
 */
abstract class ModelBasic
{

    private $id = 0;
    private $initSuccess = false;
    /**
     * @var array
     */

    abstract public static function getTableName();
    abstract public static function getDB();
    /**
     * @return array
     * array_keys(get_class_vars(self::class))
     * Es werden alle Properties fuer die Klasse definiert
     */
    abstract protected function getProperties();

    function getId()
    {
        return $this->id;
    }

    /**
     * Wurde das Objekt erfolgreich instanziiert
     */
    public function isInitSuccess()
    {
        return $this->initSuccess;
    }


    public function get($id)
    {
        if (!$result = Datenbank::get($this::getDB(), $this::getTableName(), $id)) {
            return false;
        }

        foreach ($result as $key => $value) {
            $this->$key = $value;
        }

        return $this->initSuccess = true;
    }

    public function setProperties(array $arr)
    {

        foreach ($arr as $key => $value) {
            $this->$key = $value;
        }
    }

    public function save()
    {

        if ($this->id != 0) {
            // Update

            return Datenbank::update($this::getDB(), $this::getTableName(), $this->getPropsArray(), $this->id);
        } else {

            // Alles gesetzt
            $this->isAllSet();

            return $this->id  = Datenbank::create($this::getDB(), $this::getTableName(), $this->getPropsArray());;
        }
    }

    public function delete()
    {

        return Datenbank::delete($this::getDB(), $this::getTableName(), $this->id);
    }


    private function getPropsArray()
    {
        $arr = [];
        foreach ($this->getProperties() as $value) {

            $arr[$value] = $this->$value;
        }

        return $arr;
    }

    private function isAllSet()
    {

        $required = $this instanceof ModelProps ? $this->requiredProps() : $this->getProperties();

        foreach ($required as $value) {
            if ($this->$value == null || $this->$value == "") {
                throw new Exception("Es wurden nicht alle Props fuer die die Instanz gesetzt. Speichern nocht moeglich " . $value, 500);
            }
        }
    }
}
