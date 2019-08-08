<?php

namespace Vendor\Dbapi\Klassen\Tools;

use Exception;
use Vendor\Dbapi\Klassen\Datenbank\Datenbank;

class APISimple
{
    protected $availableMethods = ["_get", "_post", "_patch", "_delete"];

    private $_get, $_post, $_patch, $_delete;


    protected function get()
    {
        if (!$this->_get)
            throw new Exception("Request Method not Allowed", 405);
        call_user_func($this->_get);
    }

    protected function post()
    {
        if (!$this->_post)
            throw new Exception("Request Method not Allowed", 405);
        call_user_func($this->_post);
    }
    protected function patch()
    {
        if (!$this->_patch)
            throw new Exception("Request Method not Allowed", 405);
        call_user_func($this->_patch, $this->getParamBody());
    }
    protected function delete()
    {
        if (!$this->_delete)
            throw new Exception("Request Method not Allowed", 405);

        call_user_func($this->_delete);
    }
    protected function options()
    {
        header("access-control-allow-methods: " . implode(",", $this->getAllowedMethods()));
        header("access-control-allow-origin: " . getallheaders()['Host']);
        header("access-control-allow-credentials: true");
        header("access-control-allow-headers: content-type");
    }

    public function setGet($func)
    {

        if (!is_callable($func)) {
            throw new Exception("Argument must be an function");
        }
        $this->_get = $func;
    }
    public function setPost($func)
    {

        if (!is_callable($func)) {
            throw new Exception("Argument must be an function");
        }
        $this->_post = $func;
    }
    public function setPatch($func)
    {

        if (!is_callable($func)) {
            throw new Exception("Argument must be an function");
        }
        $this->_patch = $func;
    }
    public function setDelete($func)
    {

        if (!is_callable($func)) {
            throw new Exception("Argument must be an function");
        }
        $this->_delete = $func;
    }

    public function run()
    {
        self::setJSONHeader();
        try {
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    return $this->get();
                case "POST":

                    return $this->post();
                case 'PATCH':

                    return $this->patch();
                case 'DELETE':
                    return $this->delete();
                case 'OPTIONS':

                    return $this->options();

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

    public static function setJSONHeader()
    {
        header('Content-Type: application/json');
    }

    protected function getParamBody()
    {
        return $this->parse(file_get_contents("php://input"));
    }


    /**
     * Parst den Input. Entweder key/value oder ein json string
     * @param string 
     * @return array
     * @throws Exception
     */
    protected function parse($string)
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

    public function getAllowedMethods()
    {
        $res = [];

        foreach ($this->availableMethods as $value) {

            if (is_callable($this->$value)) {
                $res[] = substr(strtoupper($value), 1);
            }
        }

        return $res;
    }
}
