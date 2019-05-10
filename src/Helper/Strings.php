<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 1/14/18
 * Time: 8:27 PM
 */
namespace SNOWGIRL_CORE\Helper;

/**
 * Class Strings
 *
 * @package SNOWGIRL_CORE\Helper
 */
class Strings
{
    public static function replaceBracketsParams($string, array $params)
    {
        return str_replace(array_map(function ($i) {
            return '{' . $i . '}';
        }, array_keys($params)), $params, $string);
    }
}