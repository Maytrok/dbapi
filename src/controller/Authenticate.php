<?php

namespace dbapi\controller;

use Exception;
use dbapi\interfaces\Authenticate as DbapiAuthenticate;

class Authenticate extends APISimple
{

    private $model = null;


    public function __construct($model)
    {

        if (!$model instanceof DbapiAuthenticate) {
            throw new Exception("Model in Authenticate needed to be an instance of Authenticate Model", 1);
        }
        $this->model = $model;


        $this->setPost(function ($params) {
            $username = $params['user'];
            $password = $params['password'];

            $res = $this->model->login($username, $password);

            if (false !== $res) {
                echo json_encode($res);
            } else {
                throw new Exception("User unknown or Password is wrong", 403);
            }
        }, ['user', "password"]);
    }

    protected function delete()
    {
        if ($this->model->logout()) {
            echo json_encode(['erfolg' => true]);
        } else {
            throw new Exception("Error on Logout", 400);
        }
    }


    protected function getExpireTime()
    {
        return 2000;
    }
}
