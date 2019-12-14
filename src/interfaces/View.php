<?php

namespace dbapi\interfaces;

use Exception;

interface View
{

    /**
     * @param Exception $th
     */
    public function error(Exception $th);
    public function output();
    /**
     * @param array $array
     */
    public function setMainData($array);
    /**
     * @return array
     */
    public function getMainData();
    /**
     * @param array $array
     */
    public function setData($array);
    public function setEncoding();
}
