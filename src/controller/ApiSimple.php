<?php

namespace dbapi\controller;

use Exception;
use dbapi\exception\BadRequestException;
use dbapi\exception\LoggerExceptionCrit;
use dbapi\exception\NotAuthorizedException;
use dbapi\exception\RequestMethodNotAllowedException;
use dbapi\interfaces\Authenticate;
use dbapi\interfaces\AuthoricateMethod;
use dbapi\interfaces\View;
use dbapi\tools\App;
use dbapi\views\JsonView;

class ApiSimple
{
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

    protected static $bodyparam = "";

    public static $SANITIZE_INPUT = true;

    /**
     * @var View
     */
    protected $view = null;


    private $privView = null;

    public function __construct()
    {
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
        header("access-control-allow-origin: *");
        header("access-control-allow-credentials: true");
        header("access-control-allow-headers: content-type");
    }

    public function setGet($func, $requiredParams = [])
    {

        $this->validSetMethodParams($func, $requiredParams);
        if (count($requiredParams) != 0) {
            $this->requiredParams["get"] = $requiredParams;
        }
        $this->_get = $func;
    }
    /**
     * @param callable $func
     * @param array $requiredParams
     * Function($bodyParams)
     */
    public function setPost($func, $requiredParams = [])
    {

        $this->validSetMethodParams($func, $requiredParams);
        if (count($requiredParams) != 0) {
            $this->requiredParams["post"] = $requiredParams;
        }
        $this->_post = $func;
    }
    public function setPatch($func, $requiredParams = [])
    {

        $this->validSetMethodParams($func, $requiredParams);

        if (count($requiredParams) != 0) {
            $this->requiredParams["patch"] = $requiredParams;
        }
        $this->_patch = $func;
    }
    public function setDelete($func, $requiredParams = [])
    {

        $this->validSetMethodParams($func, $requiredParams);
        if (count($requiredParams) != 0) {
            $this->requiredParams["delete"] = $requiredParams;
        }
        $this->_delete = $func;
    }

    private final function validSetMethodParams($func, $param)
    {

        try {
            if (!is_callable($func)) {
                throw new LoggerExceptionCrit("Argument must be an function", 500);
            }
            if (!is_array($param)) {
                throw new LoggerExceptionCrit("requiredParams must be an array", 500);
            }
        } catch (Exception $th) {
            $this->handleError($th);
        }
    }

    public function run()
    {
        try {

            $this->view = $this->getView();

            if (!is_null(self::$auth)) {
                $this->checkAuthUser();
                $this->checkAuthoricate();
            }
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

            if (is_null($this->view)) {

                throw new LoggerExceptionCrit("Hooks has to return an View", 500);
            }

            if ($this->_hook_output != null) {
                $this->view = call_user_func($this->_hook_output, $this->view, $_SERVER['REQUEST_METHOD']);
            }

            $this->output();
        } catch (\Exception $th) {
            $this->handleError($th);
        }
    }

    protected function output()
    {
        $this->view->output();
    }

    protected final function handleError(Exception $e)
    {

        $this->view = $this->getView();
        $this->view->error($e);
    }

    public static function getParamBody()
    {
        if (self::$bodyparam == "") {

            self::$bodyparam = self::parse(file_get_contents("php://input"));
        }
        return self::$bodyparam;
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
            $in[$item[0]] = self::sanitize(urldecode($item[1]));
        }

        if (!is_array($in)) {
            throw new BadRequestException("Can not Parse Parameter");
        }

        return $in;
    }

    public static function sanitize($str)
    {

        return self::$SANITIZE_INPUT ? filter_var($str, FILTER_SANITIZE_SPECIAL_CHARS) : $str;
    }

    public static function setAuth($auth)
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
            throw new LoggerExceptionCrit("Parameter has to be an Funktion", 500);
        }
        $this->_hook_output = $fnc;
    }

    /**
     * @return View
     */
    protected function getView()
    {
        return $this->privView == null ? new JsonView : $this->privView;
    }

    public function setView(View $view)
    {
        $this->privView = $view;
    }



    protected function checkAuthUser()
    {
        if (self::$auth instanceof Authenticate) {
            $d = null;
            self::$auth->authenticate($d);
        }
    }

    protected function generateNewJWT()
    {
        if (self::$auth instanceof Authenticate) {

            if (self::$generateNewTokenOnCall) {
                self::$auth->generateToken();
                header("JWT: " . self::$auth->getToken());
            }
        }
    }

    protected final function checkAuthoricate()
    {

        $auth = self::$auth;
        // Is Authocirate implemented
        if ($auth instanceof AuthoricateMethod) {

            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':

                    if ($auth->allowGet() !== true) {
                        throw new NotAuthorizedException();
                    }
                    return true;
                case "POST":
                    if ($auth->allowPost() !== true) {
                        throw new NotAuthorizedException();
                    }
                    return true;
                case 'PATCH':
                    if ($auth->allowPatch() !== true) {
                        throw new NotAuthorizedException();
                    }
                    return true;
                case 'DELETE':
                    if ($auth->allowDelete() !== true) {
                        throw new NotAuthorizedException();
                    }
                    return true;
            }
        }
    }
}
