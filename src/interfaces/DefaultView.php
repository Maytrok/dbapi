<?php

namespace dbapi\interfaces;

use Exception;

interface DefaultView
{

    /**
     * @param Exception
     */
    public function error(Exception $th);
    public function output();
    /**
     * @param array
     */
    public function setMainData($array);
    /**
     * @return array
     */
    public function getMainData();
    /**
     * @param array
     */
    public function setData($array);
    public function setEncoding();
}
