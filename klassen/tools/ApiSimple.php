<?php

namespace Vendor\Dbapi\Klassen\Tools;

use Exception;
use Throwable;
use Vendor\Dbapi\Klassen\Datenbank\Datenbank;

class APISimple
{
    protected $availableMethods = ["_get", "_post", "_patch", "_delete"];

    protected $reservedQueryParams = ['id', 'page', "per_page", "count"];
    private $proxyGetParams = [];

    private $_get, $_post, $_patch, $_delete;
    protected $requiredParams = [];


    protected function get()
    {
        if (!$this->_get)
            throw new Exception("Request Method not Allowed", 405);

        $this->checkRequiredParams("get", $_GET);
        call_user_func($this->_get);
    }

    protected function post()
    {
        if (!$this->_post)
            throw new Exception("Request Method not Allowed", 405);

        $this->checkRequiredParams("post", $_POST);
        call_user_func($this->_post);
    }
    protected function patch()
    {
        if (!$this->_patch)
            throw new Exception("Request Method not Allowed", 405);
        $params = $this->getParamBody();
        $this->checkRequiredParams("patch", $params);
        call_user_func($this->_patch, $params);
    }
    protected function delete()
    {
        if (!$this->_delete)
            throw new Exception("Request Method not Allowed", 405);

        $params = $this->getParamBody();
        $this->checkRequiredParams("delete", $params);
        call_user_func($this->_delete, $params);
    }
    protected function options()
    {
        header("access-control-allow-methods: " . implode(",", $this->getAllowedMethods()));
        header("access-control-allow-origin: " . getallheaders()['Host']);
        header("access-control-allow-credentials: true");
        header("access-control-allow-headers: content-type");
    }

    public function setGet($func, $requiredParams = [])
    {

        if (!is_callable($func)) {
            throw new Exception("Argument must be an function");
        }
        if (count($requiredParams) != 0) {
            $this->requiredParams["get"] = $requiredParams;
        }
        $this->_get = $func;
    }
    public function setPost($func, $requiredParams = [])
    {

        if (!is_callable($func)) {
            throw new Exception("Argument must be an function");
        }
        if (count($requiredParams) != 0) {
            $this->requiredParams["post"] = $requiredParams;
        }
        $this->_post = $func;
    }
    public function setPatch($func, $requiredParams = [])
    {

        if (!is_callable($func)) {
            throw new Exception("Argument must be an function");
        }

        if (count($requiredParams) != 0) {
            $this->requiredParams["patch"] = $requiredParams;
        }
        $this->_patch = $func;
    }
    public function setDelete($func, $requiredParams = [])
    {

        if (!is_callable($func)) {
            throw new Exception("Argument must be an function");
        }
        if (count($requiredParams) != 0) {
            $this->requiredParams["delete"] = $requiredParams;
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
            $this->handleError($th);
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
     * @param string $string
     * @return array
     * @throws Exception
     */
    protected function parse($string)
    {

        if (strlen($string) == 0) {
            throw new Exception("No Arguments were given", 400);
        }
        if (key_exists('Content-Type', getallheaders())) {

            if (in_array(getallheaders()['Content-Type'], ["application/json", "application/json;charset=utf-8"])) {
                return json_decode($string, true);
            }
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
     * @return array With additional Get Params
     */
    protected function getAdditionalGetParams()
    {

        if (count($this->proxyGetParams) == 0) {

            foreach ($_GET as $key => $value) {

                //Ignore id
                if (in_array($key, $this->reservedQueryParams)) {
                    continue;
                }


                $this->proxyGetParams[$key] =  $value;
            }
        }

        return $this->proxyGetParams;
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

    /**
     * Required Params provided
     * @param string $method Request Method
     * @param array $array $request array
     * @return bool
     */
    protected function checkRequiredParams($method, $array)
    {

        if (key_exists($method, $this->requiredParams)) {

            foreach ($this->requiredParams[$method] as $value) {

                if (!key_exists($value, $array)) {
                    throw new Exception("Required Param " . $value . " missing", 400);
                }
            }
        } else {
            return true;
        }
    }

    protected function handleError(Throwable $th)
    {
        $code = $th->getCode() == 0 ? 500 : $th->getCode();
        if (!is_int($code)) {
            http_response_code(400);
        } else {

            http_response_code($code);
        }

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
