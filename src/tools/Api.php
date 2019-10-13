<?php

namespace dbapi\tools;

use Exception;
use dbapi\model\ModelBasic;
use dbapi\db\Database;
use dbapi\interfaces\ModelProps;
use dbapi\interfaces\RestrictedView;

class Api extends APISimple
{

    /**
     * @var ModelBasic
     */
    private  $model;

    private $authvalue;

    private $_hook_output = null, $_hook_checkAuth;

    public function __construct(ModelBasic $_model, $authvalue = null)
    {
        $this->model = $_model;
        $this->authvalue = $authvalue;
    }

    protected function get()
    {

        $this->isMethodAllowed("get");
        // Single
        if (isset($_GET['id'])) {

            if ($this->authvalue != null && $this->model instanceof RestrictedView) {
                $this->model->get($_GET['id']);
                if ($this->model->restrictedValue() != $this->authvalue) {
                    throw new Exception("Not authorized", 403);
                } else {
                    Database::$throwExceptionOnNotFound = true;
                    $this->output(Database::get($this->model::getDB(), $this->model::getTableName(), $_GET['id']));
                }
            } else {
                Database::$throwExceptionOnNotFound = true;
                $this->output(Database::get($this->model::getDB(), $this->model::getTableName(), $_GET['id']));
                exit();
            }
        } else if (count($this->getAdditionalGetParams()) != 0) {
            // Specific querys

            if ($pagination = $this->isPageination()) {

                list($page, $per_page) = $pagination;

                $this->handleMultipleResults(Database::where($this->model::getDB(), $this->model::getTableName(), $this->getAdditionalGetParams(), $per_page, $page));
            } else {
                if ($this->isCountRequest()) {
                    $this->output(['count' => Database::countResults($this->model::getDB(), $this->model::getTableName(), $this->getAdditionalGetParams())]);
                } else {
                    $this->handleMultipleResults(Database::where($this->model::getDB(), $this->model::getTableName(), $this->getAdditionalGetParams()));
                }
            }
        } else {
            if ($pagination = $this->isPageination()) {
                list($page, $per_page) = $pagination;
                $this->handleMultipleResults(Database::getAll($this->model::getDB(), $this->model::getTableName(), $per_page, $page));
            } else {

                if ($this->isCountRequest()) {
                    $this->output(['count' => Database::countResults($this->model::getDB(), $this->model::getTableName())]);
                } else {

                    $this->handleMultipleResults(Database::getAll($this->model::getDB(), $this->model::getTableName()));
                }
            }
        }
    }

    protected function post()
    {
        $this->isMethodAllowed("post");
        $in = array_merge($_POST, $this->getParamBody());
        $this->checkParams($in);
        $this->model->setProperties($in);
        $this->checkAuth();
        $this->saveModel(201);


        return;
    }

    protected function patch()
    {
        $this->isMethodAllowed("patch");
        $in = $this->getParamBody();
        $id = $this->getRequestID();
        $this->initModel($id);
        $this->checkAuth();

        $this->model->setProperties($in);
        $this->saveModel();
        return;
    }

    protected function delete()
    {

        $this->isMethodAllowed("delete");
        $id = $this->getRequestID();
        $this->initModel($id);
        $this->checkAuth();
        $this->model->delete();

        $this->output(["erfolg" => true, "id" => $this->model->getId()]);

        return;
    }

    /**
     * Laed die Instanz
     * @param int $id
     * @throws Exception Exception
     */
    private function initModel($id)
    {
        $this->model->get($id);

        if (!$this->model->isInitSuccess()) {
            throw new Exception("Not Found", 404);
        }
    }



    /**
     * Speichert ein neues Modell ab
     * @throws Exception Exception
     * @return bool
     */
    private function saveModel($statuscode = 200)
    {
        $res = $this->model->save();
        if ($res === false) {
            throw new Exception("Database Exception", 500);
        } else if (is_numeric($res)) {

            if ($statuscode != 200) {
                http_response_code(201);
            };
            $res = Database::get($this->model::getDB(), $this->model::getTableName(), $this->model->getId());
            $this->output($res);
            return true;
        } else {
            return false;
        }
    }


    /**
     * Wurden bei der Anfrage alle notwendigen Parameter gesetzt
     * @return bool
     * @throws Exception
     */
    private function checkParams($input)
    {

        $req = $this->model instanceof ModelProps ? $this->model->requiredProps() : $this->model->getProperties();

        foreach ($req as $value) {
            if (!in_array($value, array_keys($input))) {
                throw new Exception("Missing Parameter:" . $value, 400);
            }
        }
        return true;
    }

    /**
     * Wurde die ID richtig gesetzt
     * @return int $id
     * @throws Exception
     */
    private function getRequestID()
    {
        if (isset($_GET['id'])) {

            if (!is_numeric($_GET['id'])) {
                throw new Exception("Malformed ID", 400);
            }
            return $_GET['id'];
        } else {
            throw new Exception("The ID has not been provided", 405);
        }
    }

    /**
     * Gibt alle erlaubten Methoden zurueck
     * @return array $array
     */
    public function getAllowedMethods()
    {
        $res = [];

        foreach ($this->availableMethods as $value) {

            $res[] = substr(strtoupper($value), 1);
        }

        $res[] = "OPTIONS";

        return $res;
    }
    /**
     * Disallow Method
     * @param string|array $methods
     */
    public function disallowMethod($methods)
    {

        if (is_array($methods)) {
            foreach ($methods as $value) {
                $this->removeMethodFromArray($value);
            }
        } else {
            $this->removeMethodFromArray($methods);
        }
    }

    private function removeMethodFromArray($method)
    {
        if (!in_array($method, $this->availableMethods)) {
            throw new Exception("Methode is not included in the Array. Please make sure you add a '_' before the Method name", 1);
        }
        array_splice($this->availableMethods, array_search($method, $this->availableMethods), 1);
    }

    private function handleMultipleResults($res)
    {
        if (count($res) == 0) {
            throw new Exception("No matching resources found", 404);
        }

        // Restriction

        if ($this->model instanceof RestrictedView && $this->authvalue != null) {
            $this->output($res);
        } else {

            $this->output($res);
        }
    }

    protected function isPageination()
    {

        if (key_exists('page', $_GET) && key_exists("per_page", $_GET)) {

            return [$_GET['page'], $_GET['per_page']];
        } else {
            return false;
        }
    }

    protected function isCountRequest()
    {
        return key_exists("count", $_GET);
    }

    /**
     * @throws Exception
     */
    protected function checkAuth()
    {
        if ($this->_hook_checkAuth != null) {
            call_user_func($this->_hook_checkAuth, $this->model, $_SERVER['REQUEST_METHOD']);
        }

        if ($_SERVER['REQUEST_METHOD'] != "POST") {

            if ($this->model instanceof RestrictedView && $this->authvalue != null) {

                if ($this->model->restrictedValue() != $this->authvalue) {
                    throw new Exception("Not authorized", 403);
                }
            }
        }
    }


    public function hookOutput($fnc)
    {
        if (!is_callable($fnc)) {
            throw new Exception("Parameter has to be an Funktion", 500);
        }
        $this->_hook_output = $fnc;
    }

    public function hookAuth($fnc)
    {
        if (!is_callable($fnc)) {
            throw new Exception("Parameter has to be an Funktion", 500);
        }
        $this->_hook_checkAuth = $fnc;
    }

    protected function output($result)
    {
        if ($this->_hook_output != null) {
            $result = call_user_func($this->_hook_output, $result, $_SERVER['REQUEST_METHOD']);
        }

        if ($result !== false && is_array($result)) {
            echo json_encode($result);
        }
    }

    protected function isMethodAllowed($method)
    {
        if (!in_array("_" . strtolower($method), $this->availableMethods)) {
            throw new Exception("Request Method not Allowed", 405);
        }
    }
}
