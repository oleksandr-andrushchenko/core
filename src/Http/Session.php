<?php

namespace SNOWGIRL_CORE\Http;

class Session
{
    protected $request;

    public function __construct(HttpRequest $request)
    {
        $this->request = $request;

        if (!session_id()) {
            session_start();
        }
    }

    public function _isset(string $k): bool
    {
        return array_key_exists($k, $_SESSION ?? []);
    }

    public function set(string $k, $v = null): Session
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

    public function get(string $k, $default = null)
    {
        return $this->_isset($k) ? $_SESSION[$k] : $default;
    }

    public function pop(string $k, $default = null)
    {
        $v = $this->get($k, $default);
        $this->_unset($k);

        return $v;
    }

    public function _unset(string $k): Session
    {
        $_SESSION[$k] = null;
        unset($_SESSION[$k]);

        return $this;
    }

    public function append(string $k, $v): Session
    {
        $_SESSION[$k][] = $v;

        return $this;
    }

    public function merge(string $k, array $v): Session
    {
        $_SESSION[$k] = array_merge((array)$_SESSION[$k], $v);

        return $this;
    }
}
