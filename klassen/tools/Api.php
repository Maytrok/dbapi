<?php

namespace Vendor\Dbapi\Klassen\Tools;

use Exception;
use Vendor\Dbapi\Klassen\Datenbank\ModelBasic;
use Vendor\Dbapi\Klassen\Datenbank\Datenbank;
use Vendor\Dbapi\Interfaces\ModelProps;

class API
{

    /**
     * @var ModelBasic
     */
    private  $model;
    public function __construct(ModelBasic $_model)
    {
        $this->model = $_model;
    }

    public function run()
    {
        self::setJSONHeader();
        try {
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    // Single
                    if (isset($_GET['id'])) {
                        echo json_encode(Datenbank::get($this->model::getDB(), $this->model::getTableName(), $_GET['id']));
                    } else {
                        echo json_encode(Datenbank::getAll($this->model::getDB(), $this->model::getTableName()));
                    }
                    return;
                case "POST":
                    $in = in_array(getallheaders()['Content-Type'], ["application/json", "application/json;charset=utf-8"])  ? $this->getParamBody() : $_POST;
                    $this->checkParams($in);
                    $this->model->setProperties($in);
                    $this->saveModel(201);

                    return;
                case 'PATCH':
                    $in = $this->getParamBody();
                    $id = $this->idExists();
                    $this->initModel($id);
                    $this->model->setProperties($in);
                    $this->saveModel();
                    return;
                case 'DELETE':
                    $id = $this->idExists();
                    $this->initModel($id);
                    $this->model->delete();

                    echo json_encode(["erfolg" => true]);

                    return;
                case 'OPTIONS':
                    header("access-control-allow-methods: GET,POST,PATCH,DELETE");
                    header("access-control-allow-origin: http://localhost:8080");
                    header("access-control-allow-credentials: true");
                    header("access-control-allow-headers: content-type");
                    return;

                default;
                    throw new Exception("Request Method not Allowed", 405);
            }
        } catch (\Throwable $th) {
            $code = $th->getCode() == 0 ? 500 : $th->getCode();
            http_response_code($code);

            if (App::$DEBUG) {
                echo json_encode([
                    "error" => $th->getMessage(), "trace" => $th->getTrace(),
                    "DB" => Datenbank::getPDO()->errorInfo()
                ]);
            } else {
                echo json_encode(["error" => $th->getMessage()]);
            }
        }
    }

    /**
     * Laed die Instanz
     * @param int
     * @throws Exeption
     */
    private function initModel($id)
    {
        $this->model->get($id);

        if (!$this->model->isInitSuccess()) {
            throw new Exception("Not Found", 404);
        }
    }

    private function getParamBody()
    {
        return $this->parse(file_get_contents("php://input"));
    }

    /**
     * Speichert ein neues Modell ab
     * @throws Exception
     */
    private function saveModel($statuscode = 200)
    {
        $res = $this->model->save();
        if ($res === false) {
            throw new Exception("Database Exception", 500);
            return false;
        } else if (is_numeric($res)) {

            if ($statuscode != 200) {
                http_response_code(201);
            };
            $res = Datenbank::get($this->model::getDB(), $this->model::getTableName(), $this->model->getId());
            echo json_encode($res);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Parst den Input. Entweder key/value oder ein json string
     * @param string 
     * @return array
     * @throws Exception
     */
    private function parse($string)
    {
        if (in_array(getallheaders()['Content-Type'], ["application/json", "application/json;charset=utf-8"])) {
            return json_decode($string, true);
        }
        $temp = explode("&", $string);
        $in = [];
        foreach ($temp as $value) {
            $item = explode("=", $value);
            $in[$item[0]] = urldecode($item[1]);
        }

        if (!is_array($in)) {
            throw new Exception("Can not Parse Parameter", 400);
        }

        return $in;
    }

    /**
     * Wurden bei der Anfrage alle notwendigen Parameter gesetzt
     * @return bool
     * @throws Exception
     */
    private function checkParams($input)
    {

        $req = $this->model instanceof ModelProps ? $this->model->requiredProps() : $this->model->getProperies();

        foreach ($req as $value) {
            if (!in_array($value, array_keys($input))) {
                throw new Exception("Missing Parameter:" . $value, 400);
            }
        }
        return true;
    }

    /**
     * Wurde die ID richtig gesetzt
     */
    private function idExists()
    {
        if (isset($_GET['id'])) {

            if (!is_numeric($_GET['id'])) {
                throw new Exception("Malformed ID", 400);
            }
            return $_GET['id'];
        } else {
            throw new Exception("The ID has not been provided", 405);
            return false;
        }
    }

    public static function setJSONHeader()
    {
        header('Content-Type: application/json');
    }
}
