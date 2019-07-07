<?php

namespace SNOWGIRL_CORE;

abstract class Builder
{
    /** @var App */
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function __get($k)
    {
        return $this->$k = $this->_get($k);
    }

    protected function _get($k)
    {
        return null;
    }

    public function __call($fn, array $args)
    {
        return $this->_call($fn, $args);
    }

    protected function _call($fn, array $args)
    {
        return null;
    }
}