<?php

namespace SNOWGIRL_CORE;

abstract class Builder
{
    /** @var AbstractApp */
    protected $app;

    public function __construct(AbstractApp $app)
    {
        $this->app = $app;
    }

    public function __get($k)
    {
        return $this->$k = $this->_get($k);
    }

    protected function _get($k)
    {
        $k && false;

        return null;
    }

    public function __call($fn, array $args)
    {
        return $this->_call($fn, $args);
    }

    protected function _call($fn, array $args)
    {
        $fn && $args && false;

        return null;
    }
}