<?php

namespace dbapi\model;


use Exception;
use dbapi\interfaces\ModelProps;
use dbapi\db\Database;
use dbapi\exception\NotFoundException;
use dbapi\tools\App;

/**
 * public static function getTableName();
 * public static function getDB();
 * /**
 * @ return array
 * All Properties, aside from id has to be in this array
 * abstract public function getProperties();
 */
abstract class ModelBasic
{

    private $id = 0;
    private $initSuccess = false;

    abstract public static function getTableName();
    abstract public static function getDB();
    /**
     * @return array
     * array_keys(get_class_vars(self::class))
     * All Properties, aside from id has to be in this array
     */
    abstract public function getProperties();

    function getId()
    {
        return $this->id;
    }

    /**
     * Init successful?
     */
    public function isInitSuccess()
    {
        return $this->initSuccess;
    }


    /**
     * @param int $id
     * @throws Exception
     * @return bool
     */
    public function get($id)
    {

        if (!$result = Database::get($this::getDB(), $this::getTableName(), $id)) {
            throw new NotFoundException($this->noRessourceFound());
        }

        $this->setProperties($result);

        return $this->initSuccess = true;
    }

    /**
     * @param array $arr
     * Updates several properties
     * Arraykey = Propertie Name
     */
    public function setProperties(array $arr)
    {

        foreach ($arr as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Create or Update the Model
     * @throws Exception
     * @return mixed ID or number of Rows updated. 0 on fail
     */
    public function save()
    {

        if ($this->id != 0) {
            // Update
            return Database::update($this::getDB(), $this::getTableName(), $this->getPropsArray(), $this->id);
        } else {
            // Alles gesetzt
            $this->isAllSet();

            return $this->id  = Database::create($this::getDB(), $this::getTableName(), $this->getPropsArray());;
        }
    }

    /**
     * deletes the current model
     * @throws Exception
     */
    public function delete()
    {
        if ($this->initSuccess) {

            return Database::delete($this::getDB(), $this::getTableName(), $this->id);
        } else {
            throw new Exception("Model wasn't fully initiated", 500);
        }
    }


    protected function getPropsArray()
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
                App::$looger->warning("missing property", $this->getPropsArray());
                throw new Exception("Not all properties were set for the instance. Saving not possible: " . $value, 500);
            }
        }
    }

    public function __clone()
    {
        $this->id = 0;
    }

    /**
     * @param array $params
     * @throws Exception
     * @return bool
     */
    public function where($params)
    {
        $result = Database::where($this::getDB(), $this::getTableName(), $params);

        if (count($result) == 0) {
            throw new NotFoundException($this->noRessourceFound());
        }


        if (count($result) > 1) {
            throw new Exception("To many Result for the where Request", 413);
        }


        $this->setProperties($result[0]);
        return $this->initSuccess = true;
    }

    /**
     * Get all DB entries
     * @throws NotFoundException
     */
    public static function all()
    {
        $t = get_called_class();
        return Database::getAll($t::getDB(), $t::getTableName());
    }

    /**
     * @param string $key
     * @param string $value
     * @param string $mod default =
     * @return array
     */
    public static function findWhere($key, $value, $mod = "=")
    {
        $t = get_called_class();
        return Database::where($t::getDB(), $t::getTableName(), [$key => [$value, $mod]]);
    }

    /**
     * @return ModelBasic|null
     */
    public static function find($id)
    {
        $class = get_called_class();
        $model = new $class;
        try {
            $model->get($id);
            return $model;
        } catch (Exception $th) {
            return null;
        }
    }

    /**
     * @throws NotFoundException
     * @return ModelBasic
     */
    public static function findOrFail($id)
    {
        $class = get_called_class();
        $model = new $class;

        $model->get($id);
        return $model;
    }

    protected function noRessourceFound()
    {
        return "Ressource not found";
    }
}
