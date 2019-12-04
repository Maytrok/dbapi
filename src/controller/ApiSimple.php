<?php

namespace dbapi\controller;

use Exception;
use dbapi\exception\BadRequestException;
use dbapi\exception\RequestMethodNotAllowedException;
use dbapi\interfaces\Authenticate;
use dbapi\tools\App;
use dbapi\tools\HttpCode;
use dbapi\views\DefaultView;

class ApiSimple
{
    /**
     * @var Authenticate
     */
    public static $auth = null;

    public static $generateNewTokenOnCall = false;


    protected $availableMethods = ["_get", "_post", "_patch", "_delete"];

    protected $reservedQueryParams = ['id', 'page', "per_page", "count"];
    private $proxyGetParams = [];

    private $_get, $_post, $_patch, $_delete;
    protected $requiredParams = [];

    protected $_hook_output = null;



    public static $DELETE = "_delete";
    public static $POST = "_post";
    public static $PATCH = "_patch";
    public static $GET = "_get";

    /**
     * @var DefaultView
     */
    public $view = null;

    public function __construct()
    {
        $this->view = $this->getView();
    }

    protected function get()
    {
        if (!$this->_get) {

            throw new RequestMethodNotAllowedException();
        }

        $this->checkRequiredParams("get", $_GET);
        $this->view = call_user_func($this->_get);
    }

    protected function post()
    {
        if (!$this->_post) {
            throw new RequestMethodNotAllowedException();
        }


        $params = array_merge($_POST, ApiSimple::getParamBody());
        $this->checkRequiredParams("post", $params);
        $this->view = call_user_func($this->_post, $params);
    }
    protected function patch()
    {
        if (!$this->_patch) {
            throw new RequestMethodNotAllowedException();
        }
        $params = ApiSimple::getParamBody();
        $this->checkRequiredParams("patch", $params);
        $this->view = call_user_func($this->_patch, $params);
    }
    protected function delete()
    {
        if (!$this->_delete) {
            throw new RequestMethodNotAllowedException();
        }

        $params = ApiSimple::getParamBody();
        $this->checkRequiredParams("delete", $params);
        $this->view = call_user_func($this->_delete, $params);
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
            App::$looger->critical("Argument must be an function");
            throw new Exception("Argument must be an function", HttpCode::$INTERNAL_SERVER_ERROR);
        }
        if (count($requiredParams) != 0) {
            $this->requiredParams["get"] = $requiredParams;
        }
        $this->_get = $func;
    }
    public function setPost($func, $requiredParams = [])
    {

        if (!is_callable($func)) {
            App::$looger->critical("Argument must be an function");
            throw new Exception("Argument must be an function", HttpCode::$INTERNAL_SERVER_ERROR);
        }
        if (count($requiredParams) != 0) {
            $this->requiredParams["post"] = $requiredParams;
        }
        $this->_post = $func;
    }
    public function setPatch($func, $requiredParams = [])
    {

        if (!is_callable($func)) {
            App::$looger->critical("Argument must be an function");
            throw new Exception("Argument must be an function", HttpCode::$INTERNAL_SERVER_ERROR);
        }

        if (count($requiredParams) != 0) {
            $this->requiredParams["patch"] = $requiredParams;
        }
        $this->_patch = $func;
    }
    public function setDelete($func, $requiredParams = [])
    {

        if (!is_callable($func)) {
            App::$looger->critical("Argument must be an function");
            throw new Exception("Argument must be an function", HttpCode::$INTERNAL_SERVER_ERROR);
        }
        if (count($requiredParams) != 0) {
            $this->requiredParams["delete"] = $requiredParams;
        }
        $this->_delete = $func;
    }

    public function run()
    {
        try {
            $this->checkAuthUser();
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    App::$looger->debug("GET Request");
                    $this->get();
                    break;
                case "POST":
                    App::$looger->debug("POST Request");
                    $this->post();
                    break;
                case 'PATCH':
                    App::$looger->info("PATCH Request");
                    $this->patch();
                    break;
                case 'DELETE':
                    App::$looger->notice("DELETE Request");
                    $this->delete();
                    break;
                case 'OPTIONS':
                    $this->options();
                    return;

                default;
                    throw new RequestMethodNotAllowedException();
            }

            if ($this->_hook_output != null) {
                $this->view = call_user_func($this->_hook_output, $this->view, $_SERVER['REQUEST_METHOD']);
            }

            if (!is_null(self::$auth)) {

                if (self::$generateNewTokenOnCall) {
                    self::$auth->generateToken();
                }
                header("JWT: " . self::$auth->getToken());
            }
            $this->view->output();
        } catch (\Exception $th) {
            $this->view->error($th);
        }
    }

    public static function setJSONHeader()
    {
        header('Content-Type: application/json');
    }

    public static function getParamBody()
    {
        return self::parse(file_get_contents("php://input"));
    }


    /**
     * @param string $string
     * @return array|null
     * @throws Exception
     */
    public static function parse($string)
    {

        if (strlen($string) == 0) {
            return [];
        }
        if (key_exists('Content-Type', getallheaders())) {

            if (strstr(getallheaders()['Content-Type'], "json")) {
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
            throw new BadRequestException("Can not Parse Parameter");
        }

        return $in;
    }

    public static function setAuth(Authenticate $auth)
    {
        self::$auth = $auth;
    }

    /**
     * @return array With additional Get Params
     */
    protected function getAdditionalGetParams()
    {

        if (count($this->proxyGetParams) == 0) {
            foreach ($_GET as $key => $value) {
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
     * @throws RequestMethodNotAllowedException
     * @param string $method Request Method
     * @param array $array $request array
     * @return bool
     */
    protected function checkRequiredParams($method, $array)
    {

        if (key_exists($method, $this->requiredParams)) {

            foreach ($this->requiredParams[$method] as $value) {

                if (!key_exists($value, $array)) {
                    throw new BadRequestException("Required Param " . $value . " missing");
                }
            }
        } else {
            return true;
        }
    }

    public static final function handleOptionCall()
    {
        if ($_SERVER['REQUEST_METHOD'] == "OPTIONS") {
            header("Access-Control-Allow-Headers: content-type");
            header("access-control-allow-methods: GET,POST,DELETE,PATCH");
            header("access-control-allow-origin: " . getallheaders()['Host']);
            header("access-control-allow-credentials: true");
            exit();
        }
    }

    public function hookOutput($fnc)
    {
        if (!is_callable($fnc)) {
            App::$looger->critical("Parameter has to be an Funktion");
            throw new Exception("Parameter has to be an Funktion", HttpCode::$INTERNAL_SERVER_ERROR);
        }
        $this->_hook_output = $fnc;
    }

    /**
     * @return DefaultView
     */
    protected function getView()
    {
        return new DefaultView;
    }



    protected function checkAuthUser()
    {

        if (!is_null(self::$auth)) {
            $d = null;
            self::$auth->authenticate($d);
        }
    }
}
