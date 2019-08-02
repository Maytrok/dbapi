<?php

namespace Vendor\Dbapi\Klassen\Tools;

use Exception;

class EnvReader
{

    public $env = [];

    private $filepath = "./vendor/dbapi/.env";


    public function __construct($filepath = null)
    {
        $fp = $filepath ? $filepath : $this->filepath;

        if (!file_exists($fp)) {
            throw new Exception("Die .Env konnte nicht gefunden werden!");
        }
        @$handle = fopen($fp, "r");

        if (!$handle) {
            throw new Exception("Es ist ein fehler beim oeffnen der .Env aufgetreten");
        }

        while ($row = fgets($handle, 2000)) {
            $d = explode("=", $row);
            $this->env[$d[0]] = trim($d[1]);
        }

        fclose($handle);
    }
}
