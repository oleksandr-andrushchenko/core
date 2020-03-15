<?php

namespace SNOWGIRL_CORE;

abstract class AbstractRequest
{
    public static $controllerKey = 'controller';
    public static $actionKey = 'action';

    protected $controller;
    protected $action;
    protected $params = [];

    /**
     * @var AbstractApp
     */
    protected $app;

    public function __construct(AbstractApp $app)
    {
        $this->app = $app;
    }

    public function getController(): ?string
    {
        if (null === $this->controller) {
            $this->controller = $this->get(self::$controllerKey);
        }

        return $this->controller;
    }

    public function setController(string $value): AbstractRequest
    {
        $this->controller = $value;

        return $this;
    }

    public function getAction(): ?string
    {
        if (null === $this->action) {
            $this->action = $this->get(self::$actionKey);
        }

        return $this->action;
    }

    public function setAction(string $value): AbstractRequest
    {
        $this->action = $value;

        if (null === $value) {
            $this->set(self::$actionKey, $value);
        }

        return $this;
    }

    public function get(string $key, $default = null)
    {
        switch (true) {
            case isset($this->params[$key]):
                return $this->params[$key];
            default:
                return $default;
        }
    }

    public function __get(string $key)
    {
        return $this->get($key);
    }

    public function has(string $key): bool
    {
        switch (true) {
            case isset($this->params[$key]):
                return true;
            default:
                return false;
        }
    }

    public function __isset($key)
    {
        return $this->has($key);
    }

    public function set(string $key, $value): AbstractRequest
    {
        if ((null === $value) && isset($this->params[$key])) {
            unset($this->params[$key]);
        } elseif (null !== $value) {
            $this->params[$key] = $value;
        }

        return $this;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function setParams(array $params): AbstractRequest
    {
        foreach ($params as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }
}