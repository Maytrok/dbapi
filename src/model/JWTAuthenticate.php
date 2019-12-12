<?php

namespace dbapi\model;

use dbapi\controller\APISimple;
use dbapi\exception\NotAuthorizedException;
use dbapi\exception\NoValidSessionException;
use dbapi\interfaces\Authenticate;
use Firebase\JWT\JWT;
use Exception;
use dbapi\model\ModelBasic;
use dbapi\interfaces\RestrictedView;
use dbapi\tools\App;

abstract class JWTAuthenticate extends ModelBasic implements Authenticate
{

    protected $jwt = null;

    private static $id_static = null;

    private static $model = null;

    abstract protected function getJWTKeySecret();
    abstract public function getPasswort();

    public function authenticate(&$model = null)
    {

        try {
            $session_error = "Keine gültige Sitzung";

            $jwt = $this->getJWTFromHeader();
            if (!$jwt) {
                App::$looger->info($session_error);
                throw new NoValidSessionException("No JWT Header was submitted");
            };

            if (strlen($jwt) == 0) {
                App::$looger->warning($session_error);
                throw new NoValidSessionException($session_error);
            }

            $dec = JWT::decode($jwt, $this->getJWTKeySecret(), array('HS256'));
            $this->get($dec->userid);
            self::$id_static = $this->getId();
            if ($jwt != $this->getJwt()) {
                App::$looger->warning($session_error);
                throw new NoValidSessionException($session_error);
            }

            // If instance of Restricted View the Result will be affected
            if ($model != null && $model instanceof RestrictedView) {
                $key = $model->restrictedKey();

                if (key_exists($key, $_GET) || key_exists($key, $_POST)) {
                    App::$looger->warning("one of the passed parameters was overwritten. check the request: " . $key);
                    throw new NotAuthorizedException("one of the passed parameters was overwritten. check the request");
                }

                $_GET[$key] = $this->getId();
                $_POST[$key] = $this->getId();
                $key = "set" . ucfirst($key);
                $model->$key = $this->getId();
                self::$model = $this;
            }
        } catch (Exception $th) {

            http_response_code($th->getCode());
            APISimple::setJSONHeader();
            $classname = explode("\\", get_class($th));
            echo json_encode(['error' => $th->getMessage(), "exception" => $classname[count($classname) - 1]]);
            exit();
        }
    }


    private function getJWTFromHeader()
    {

        $header = getallheaders();

        foreach ($header as $key => $value) {

            if (strtolower($key) == "jwt") {
                return $value;
            }
        }
        return false;
    }

    public function login($username, $password)
    {
        $this->where(["name" => $username]);
        if ($password != $this->getPasswort()) {

            throw new Exception("Falsches Password oder Benutzername", 403);
        } else {
            return $this->generateToken();
        }
    }

    public function generateToken()
    {
        $token = array(
            'userid' => $this->getId(),
            "exp" => time() + $this->getExpireTime(),
            "iat" => 1356999524,
            "nbf" => 1357000000
        );

        $jwt = JWT::encode($token, $this->getJWTKeySecret());

        $this->setJwt($jwt);
        $this->save();
        return ['erfolg' => true, "jwt" => $jwt];
    }

    public function logout()
    {


        $jwt = $this->getJWTFromHeader();
        if ($jwt === false) {
            throw new Exception("Error on Logout. Token was not submitted", 400);
        }
        $this->where(["jwt" => $jwt]);

        $this->setJwt(null);
        return $this->save();
    }

    protected function getExpireTime()
    {
        return 2000;
    }

    public function setJwt($value)
    {
        $this->jwt = $value;
    }
    public function getJwt()
    {
        return $this->jwt;
    }

    public function getToken()
    {
        return $this->getJwt();
    }

    public static function getAuthUserId()
    {
        return self::$id_static;
    }
}
