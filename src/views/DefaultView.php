<?php

namespace dbapi\views;

use dbapi\db\Database;
use dbapi\interfaces\DefaultView as DbapiDefaultView;
use dbapi\tools\App;
use Exception;

class DefaultView implements DbapiDefaultView
{

    private $data = [];

    private $dataKey = "data";

    public $mainDataOnRootElement = false;


    public function setEncoding()
    {
        header('Content-Type: application/json');
    }

    public function output()
    {

        $this->setEncoding();

        if (!key_exists("error", $this->data)) {
            $this->data['status'] = "Ok";

            if (key_exists($this->dataKey, $this->data)) {

                if (count($this->data[$this->dataKey]) > 1) {
                    $this->data["count"] = count($this->data[$this->dataKey]);
                }
            }
        }

        echo json_encode($this->data);
    }

    public function error(Exception $th)
    {
        $code = $th->getCode() == 0 ? 500 : $th->getCode();
        if (!is_int($code)) {
            http_response_code(400);
        } else {

            http_response_code($code);
        }
        $classname = explode("\\", get_class($th));
        if (App::$DEBUG) {

            $this->data  = [
                "status" => "error",
                "error" => $th->getMessage(), "trace" => $th->getTrace(),
                "DB" => Database::getPDO()->errorInfo(),
                "exception" => $classname[count($classname) - 1]
            ];

            $this->output();
        } else {
            $this->data = ["status" => "error", "error" => $th->getMessage(), "exception" => $classname[count($classname) - 1]];
            $this->output();
        }
        //Break the App
        exit();
    }
    public function setMainData($array)
    {
        if ($this->mainDataOnRootElement) {
            $this->data[] = $array;
        } else {

            $this->data[$this->dataKey] = $array;
        }
    }
    public function setData($array)
    {
        if (key_exists($this->dataKey, $array)) {
            $this->error(new Exception("Providing the 'data' key in setData is not allowed ", 500));
            return;
        }
        $this->data = array_merge($this->data, $array);
    }
}
