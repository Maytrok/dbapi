<?php

namespace dbapi\interfaces;

/**
 * public function restrictedKey()
 */
interface RestrictedView
{
    /*
    * Key of the Property whcich limits the result may be shown
    */
    public function restrictedKey();

    /**
     * Value of the Property
     */
    public function restrictedValue();
}
