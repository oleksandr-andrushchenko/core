<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 12/29/17
 * Time: 1:59 PM
 */

namespace SNOWGIRL_CORE\Request;

use SNOWGIRL_CORE\Request;

/**
 * Class Cookie
 * @package SNOWGIRL_CORE\Request
 */
class Cookie
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function _isset($k)
    {
        return array_key_exists($k, $_COOKIE ?? []);
    }

    public function set($k, $v = '', $expire = 0, $path = '', $domain = '', $secure = false, $httpOnly = false)
    {
        setcookie($k, $v, $expire, $path, $domain, $secure, $httpOnly);
        return $this;
    }

    public function get($k, $default = null)
    {
        return $this->_isset($k) ? $_COOKIE[$k] : $default;
    }

    public function _unset($k, $path = '', $domain = '', $secure = false, $httpOnly = false)
    {
        $this->set($k, '', time() - 3600, $path, $domain, $secure, $httpOnly);
        unset($_COOKIE[$k]);
        return $this;
    }
}