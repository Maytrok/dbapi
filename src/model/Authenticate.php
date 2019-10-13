<?php

namespace dbapi\model;

use Firebase\JWT\JWT;
use Exception;
use dbapi\model\ModelBasic;
use dbapi\interfaces\RestrictedView;

abstract class Authenticate extends ModelBasic
{

    protected $jwt = null;

    private static $id = null;

    abstract protected function getJWTKeySecret();
    abstract public function getPasswort();

    public function authenticate(&$model = null)
    {
        $session_error = "Keine gültige Sitzung";
        if (!key_exists("JWT", getallheaders())) {
            throw new Exception($session_error, 403);
        };
        $jwt = getallheaders()["JWT"];
        if (strlen($jwt) == 0) {
            throw new Exception($session_error, 403);
        }

        $dec = JWT::decode(getallheaders()["JWT"], $this->getJWTKeySecret(), array('HS256'));
        $this->get($dec->userid);
        self::$id = $this->getId();
        if ($jwt != $this->getJwt()) {
            throw new Exception($session_error, 403);
        }

        if ($model != null && $model instanceof RestrictedView) {
            $key = $model->restrictedKey();
            $_GET[$key] = $this->getId();
            $_POST[$key] = $this->getId();
            $key = "set" . ucfirst($key);
            $model->$key = $this->getId();
        }
    }

    public function login()
    {

        $app = new APISimple();
        $app->setPOST(function ($params) {

            $username = $params['user'];
            $password = $params['password'];


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
                echo json_encode(['erfolg' => true, "jwt" => $jwt]);
            }
        }, ["user", "password"]);

        $app->run();
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
