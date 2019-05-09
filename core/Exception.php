<?php
/**
 * Created by JetBrains PhpStorm.
 * User: snowgirl
 * Date: 21.05.15
 * Time: 11:30
 * To change this template use File | Settings | File Templates.
 */

namespace SNOWGIRL_CORE;

/**
 * Class Exception
 * @package SNOWGIRL_CORE
 */
class Exception extends \Exception
{
    protected $isLogged;

    public function setLogged()
    {
        $this->isLogged = true;
        return $this;
    }

    public function isLogged()
    {
        return $this->isLogged;
    }

    public function check($text)
    {
        return self::_check($this, $text);
    }

    public static function _check(\Exception $ex, $text)
    {
        return false !== strpos(strtolower($ex->getMessage()), strtolower($text));
    }
}