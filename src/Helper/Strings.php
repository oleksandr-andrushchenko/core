<?php

namespace SNOWGIRL_CORE\Helper;

class Strings
{
    public static function replaceBracketsParams(string $input, array $params): string
    {
        return str_replace(array_map(function ($i) {
            return '{' . $i . '}';
        }, array_keys($params)), $params, $input);
    }

    public static function underscoreToCamelCase(string $input, $lcfirst = true): string
    {
        $output = implode('', array_map('ucfirst', explode('_', $input)));

        if ($lcfirst) {
            return lcfirst($output);
        }

        return $output;
    }
}