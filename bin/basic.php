<?php

spl_autoload_register('my_autoloader');
function my_autoloader($class)
{
    $class = str_replace("\\", "/", $class);
    $path = __DIR__ . "/../src" . str_replace("dbapi", "", $class) . '.php';
    if (file_exists($path)) {

        include $path;
    }
}

function utf8encodeArray($array)
{
    if (!is_array($array)) {
        return $array;
    }

    if (count($array) == 0) {
        return $array;
    }
    foreach ($array as $key =>  $value) {
        if (is_array($value)) {
            $array[$key] = utf8encodeArray($value);
        } elseif (!mb_detect_encoding($value, 'UTF-8', true)) {
            $array[$key] = utf8_encode($value);
        }
    }

    return $array;
}
