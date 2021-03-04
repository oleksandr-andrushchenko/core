<?php

namespace SNOWGIRL_CORE\Http;

class Session
{
    protected $request;

    public function __construct(HttpRequest $request)
    {
        $this->request = $request;

        $this->startSession();
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
        $_SESSION[$k] = array_merge((array) $_SESSION[$k], $v);

        return $this;
    }

    private function startSession()
    {
        $ok = (function () {
            $sn = session_name();

            if (isset($_COOKIE[$sn])) {
                $id = $_COOKIE[$sn];
            } else {
                return session_start();
            }

            if (!preg_match('/^[a-zA-Z0-9,\-]{22,40}$/', $id)) {
                return false;
            }

            return session_start();
        })();

        if (!$ok) {
            session_id(uniqid());
            session_start();
            session_regenerate_id();
        }
    }
}
