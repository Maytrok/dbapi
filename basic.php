<?php

spl_autoload_register('my_autoloader');
function my_autoloader($class)
{
    $class = str_replace("\\", "/", $class);
    include $class . '.php';
}

function utf8encodeArray($array)
{
    foreach ($array as $key =>  $value) {
        if (is_array($value)) {
            $array[$key] = utf8encodeArray($value);
        } elseif (!mb_detect_encoding($value, 'UTF-8', true)) {
            $array[$key] = utf8_encode($value);
        }
    }

    return $array;
}
