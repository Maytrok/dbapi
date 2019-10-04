<?php

namespace Vendor\Dbapi\Interfaces;

/**
 * public function restrictedKey()
 */
interface RestrictedView
{

    /**
     * Diese Funktion muss den Key zurückgeben, gegen den Authentifiziert werden soll
     * 
     */
    public function restrictedValue();
    public function restrictedKey();
}
