<?php

namespace dbapi\controller;

use dbapi\exception\NotAuthorizedException;
use Exception;
use dbapi\interfaces\Authenticate as DbapiAuthenticate;

class Authenticate extends APISimple
{

    private $model = null;


    public function __construct($model)
    {

        parent::__construct();
        if (!$model instanceof DbapiAuthenticate) {
            $this->view->error(new Exception("Model in Authenticate has to be an instance of Authenticate Model", 500));
        }
        $this->model = $model;


        $this->setPost(function ($params) {
            $username = $params['user'];
            $password = $params['password'];

            $res = $this->model->login($username, $password);

            if (false !== $res) {
                $this->view->setData($res);
            } else {
                $this->view->error(new NotAuthorizedException("User unknown or Password is wrong"));
            }
        }, ['user', "password"]);
    }

    protected function delete()
    {
        if ($this->model->logout()) {
            $this->view->setData(["msg" => "successfully logged out"]);
        } else {
            $this->view->error(new Exception("Error on Logout", 500));
        }
    }


    protected function getExpireTime()
    {
        return 2000;
    }
}
