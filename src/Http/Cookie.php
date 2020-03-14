<?php

namespace SNOWGIRL_CORE\Http;

class Cookie
{
    protected $request;

    public function __construct(HttpRequest $request)
    {
        $this->request = $request;
    }

    public function _isset($k): bool
    {
        return array_key_exists($k, $_COOKIE ?? []);
    }

    public function set(string $k, string $v = '', int $expire = 0, string $path = '', string $domain = '', bool $secure = false, bool $httpOnly = false): Cookie
    {
        setcookie($k, $v, $expire, $path, $domain, $secure, $httpOnly);

        return $this;
    }

    public function get(string $k, $default = null)
    {
        return $this->_isset($k) ? $_COOKIE[$k] : $default;
    }

    public function _unset(string $k, string $path = '', string $domain = '', bool $secure = false, bool $httpOnly = false): Cookie
    {
        $this->set($k, '', time() - 3600, $path, $domain, $secure, $httpOnly);
        unset($_COOKIE[$k]);

        return $this;
    }
}