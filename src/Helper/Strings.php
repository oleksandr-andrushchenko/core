<?php

namespace SNOWGIRL_CORE\Helper;

class Strings
{
    public static function replaceBracketsParams($string, array $params)
    {
        return str_replace(array_map(function ($i) {
            return '{' . $i . '}';
        }, array_keys($params)), $params, $string);
    }
}