<?php

namespace dbapi\interfaces;

use Exception;

interface DefaultView
{

    public function error(Exception $th);
    public function output();
    public function setMainData($array);
    public function setData($array);
    public function setEncoding();
}
