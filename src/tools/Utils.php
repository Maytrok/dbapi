<?php

namespace dbapi\tools;

class Utils
{

    public static function utf8encodeArray($array)
    {
        if (!is_array($array)) {
            return $array;
        }

        if (count($array) == 0) {
            return $array;
        }
        foreach ($array as $key =>  $value) {
            if (is_array($value)) {
                $array[$key] = Utils::utf8encodeArray($value);
            } elseif (!mb_detect_encoding($value, 'UTF-8', true)) {
                $array[$key] = Utils::utf8_encode($value);
            }
        }

        return $array;
    }
}
