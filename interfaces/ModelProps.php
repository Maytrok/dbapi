<?php

namespace Vendor\INTERFACES;

/**
 * Kann verwendet werden, wenn nicht alle Properties fuer das Speichern in die Datenbank benoetigt werden
 * public function requiredProps()
 */
interface ModelProps
{
    public function requiredProps();
}
