<?php
/**
 * Created by JetBrains PhpStorm.
 * User: snowgirl
 * Date: 13.11.13
 * Time: 20:56
 * To change this template use File | Settings | File Templates.
 */
namespace SNOWGIRL_CORE\Request;
use SNOWGIRL_CORE\Request;

/**
 * Class Session
 * @package SNOWGIRL_CORE\Request
 */
class Session
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;

        if (!session_id()) {
//            ini_set('session.cookie_lifetime', 86400);
//            ini_set('session.gc_maxlifetime', 86400);
            session_start();
        }
    }

    public function _isset($k)
    {
        return array_key_exists($k, $_SESSION ?? []);
    }

    public function set($k, $v = null)
    {
        if (is_array($k)) {
            foreach ($k as $key => $value) {
                $this->set($key, $value);
            }
        } else {
            $_SESSION[$k] = $v;
        }

        return $this;
    }

    public function get($k, $default = null)
    {
        return $this->_isset($k) ? $_SESSION[$k] : $default;
    }

    public function pop($k, $default = null)
    {
        $v = $this->get($k, $default);
        $this->_unset($k);
        return $v;
    }

    public function _unset($k)
    {
        $_SESSION[$k] = null;
        unset($_SESSION[$k]);
        return $this;
    }

    public function append($k, $v)
    {
        $_SESSION[$k][] = $v;
        return $this;
    }

    public function merge($k, array $v)
    {
        $_SESSION[$k] = array_merge((array)$_SESSION[$k], $v);
        return $this;
    }
}
