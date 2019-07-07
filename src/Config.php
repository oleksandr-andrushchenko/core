<?php

namespace SNOWGIRL_CORE;

use SNOWGIRL_CORE\Helper\Strings;

class Configurable extends \stdClass
{
    /**
     * @param ConfigOption|array $config
     */
    public function __construct($config)
    {
        foreach ($config as $k => $v) {
            $this->setOption($k, $v);
        }

        $this->initialize();
    }

    protected function initialize()
    {
    }

    public function setOption($k, $v)
    {
        $method = 'set' . implode('', explode(' ', ucwords(str_replace('_', ' ', $k))));

        if (method_exists($this, $method)) {
            $this->$method($v);
        } else {
            $this->$k = $v;
        }

        return $this;
    }

    public function getOption($k)
    {
        $method = 'get' . implode('', explode(' ', ucwords(str_replace('_', ' ', $k))));

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return $this->$k;
    }
}

/**
 * For 2nd level of config settings
 *
 * Class ConfigOption
 *
 * @package SNOWGIRL_CORE
 */
class ConfigOption extends Configurable
{
    public function __get($k)
    {
        return null;
//        return $this->$k = null;
    }

    /**
     * For retrieve properties with default values and replacements
     *
     * @param       $name
     * @param array $arguments
     *
     * @return mixed|null
     */
    public function __call($name, array $arguments)
    {
        if (property_exists($this, $name)) {
            $output = $this->$name;
        } else {
            $output = isset($arguments[0]) ? $arguments[0] : null;
        }

        if (isset($arguments[1]) && is_array($arguments[1])) {
            $output = Strings::replaceBracketsParams($output, $arguments[1]);
        }

        return $output;
    }
}

/**
 * For 1st level of config settings
 *
 * Class Config
 *
 * @package SNOWGIRL_CORE
 */
class Config extends ConfigOption
{
    public function __construct($parsedIniFile)
    {
        parent::__construct(array_map(function ($v) {
            return new ConfigOption($v);
        }, $parsedIniFile));
    }

    public function __get($k)
    {
        return new ConfigOption([]);
//        return $this->$k = new ConfigOption([]);
    }
}