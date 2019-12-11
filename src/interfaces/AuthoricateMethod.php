<?php

namespace dbapi\interfaces;


/**
 * This Model has to be implementet to enable authorication in Api Controller
 */
interface AuthoricateMethod
{

    public function allowGet();
    public function allowPost();
    public function allowPatch();
    public function allowDelete();
}
