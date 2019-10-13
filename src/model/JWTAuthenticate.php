<?php

namespace dbapi\model;

use dbapi\exception\NoValidSessionException;
use dbapi\interfaces\Authenticate;
use Firebase\JWT\JWT;
use Exception;
use dbapi\model\ModelBasic;
use dbapi\interfaces\RestrictedView;

abstract class JWTAuthenticate extends ModelBasic implements Authenticate
{

    protected $jwt = null;

    private static $id = null;

    abstract protected function getJWTKeySecret();
    abstract public function getPasswort();

    public function authenticate(&$model = null)
    {

        try {
            $session_error = "Keine gültige Sitzung";
            if (!key_exists("JWT", getallheaders())) {
                throw new NoValidSessionException("Not JWT Header was submitted", 403);
            };
            $jwt = getallheaders()["JWT"];
            if (strlen($jwt) == 0) {
                throw new NoValidSessionException($session_error, 403);
            }

            $dec = JWT::decode(getallheaders()["JWT"], $this->getJWTKeySecret(), array('HS256'));
            $this->get($dec->userid);
            self::$id = $this->getId();
            if ($jwt != $this->getJwt()) {
                throw new NoValidSessionException($session_error, 403);
            }

            // If instance of Restricted View the Result will be affected
            if ($model != null && $model instanceof RestrictedView) {
                $key = $model->restrictedKey();
                $_GET[$key] = $this->getId();
                $_POST[$key] = $this->getId();
                $key = "set" . ucfirst($key);
                $model->$key = $this->getId();
            }
        } catch (Exception $th) {

            http_response_code($th->getCode());

            $classname = explode("\\", get_class($th));
            echo json_encode(['error' => $th->getMessage(), "exception" => $classname[count($classname) - 1]]);
            exit();
        }
    }

    public function login($username, $password)
    {

        $this->where(["name" => $username]);

        if (!password_verify($password, $this->getPasswort())) {
            throw new Exception("Falsches Password oder Benutzername", 403);
        } else {
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
    }


    public function logout()
    {

        if (!isset(getallheaders()['JWT'])) {
            throw new Exception("Error on Logout. Token was not submitted", 400);
        }
        $jwt = getallheaders()['JWT'];

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

    public static function getAuthUserId()
    {
        return self::$id;
    }
}
