<?php

namespace SNOWGIRL_CORE;

class Route
{
    protected $route;
    protected $delimiter = '/';
    protected $variables = [];
    protected $parts = [];
    protected $defaults = [];
    protected $staticCount = 0;

    public function __construct($route, array $defaults = [], array $requires = [])
    {
        $this->route = trim($route, $this->delimiter);
        $this->defaults = $defaults;

        if ($route !== '') {
            foreach (explode($this->delimiter, $this->route) as $pos => $part) {
                if (':' == substr($part, 0, 1)) {
                    $this->parts[$pos] = isset($requires[$name = substr($part, 1)]) ? $requires[$name] : null;
                    $this->variables[$pos] = $name;
                } else {
                    $this->parts[$pos] = $part;
                    $this->staticCount++;
                }
            }
        }
    }

    public function match($path)
    {
        $output = [];

        $staticCount = 0;

        foreach (explode($this->delimiter, trim($path, $this->delimiter)) as $pos => $part) {
            if (!array_key_exists($pos, $this->parts)) {
                return false;
            }

            $name = $this->variables[$pos] ?? null;
            $part = urldecode($part);

            if ($name === null && $this->parts[$pos] != $part) {
                return false;
            }

            if (null !== $this->parts[$pos] && !preg_match('#^' . $this->parts[$pos] . '$#iu', $part, $matches)) {
                return false;
            }

            if (isset($matches)) {
                foreach ($matches as $k => $v) {
                    if (is_string($k)) {
                        $output[$k] = $v;
                    }
                }
            }

            if ($name !== null) {
                $output[$name] = $part;
            } else {
                $staticCount++;
            }
        }

        if ($this->staticCount != $staticCount) {
            return false;
        }

        $output = array_merge($this->defaults, $output);

        foreach ($this->variables as $var) {
            if (!array_key_exists($var, $output)) {
                return false;
            }

            if ($output[$var] == '' || $output[$var] === null) {
                $output[$var] = $this->defaults[$var];
            }
        }

        return $output;
    }

    public function makeLink($data = [], $encode = false)
    {
        $url = [];
        $flag = false;

        foreach ($this->parts as $key => $part) {
            $name = isset($this->variables[$key]) ? $this->variables[$key] : null;
            $useDefault = false;

            if (isset($name) && array_key_exists($name, $data) && $data[$name] === null) {
                $useDefault = true;
            }

            if (isset($name)) {
                if (isset($data[$name]) && !$useDefault) {
                    $value = $data[$name];
                    unset($data[$name]);
                } elseif (array_key_exists($name, $this->defaults)) {
                    $value = $this->defaults[$name];
                } else {
                    throw new Exception($name . ' is not specified');
                }

                $url[$key] = $value;
            } elseif ($part != '*') {
                $url[$key] = $part;
            } else {
                foreach ($data as $var => $value) {
                    if ($value !== null && (!isset($this->defaults[$var]) || $value != $this->defaults[$var])) {
                        $url[$key++] = $var;
                        $url[$key++] = $value;
                        $flag = true;
                    }
                }
            }
        }

        $return = '';

        foreach (array_reverse($url, true) as $key => $value) {
            $defaultValue = null;

            if (isset($this->variables[$key])) {
                $defaultValue = $this->getDefault($this->variables[$key]);
            }

            if ($flag || ($value !== $defaultValue)) {
                if ($encode) {
                    $value = urlencode($value);
                }

                $return = $this->delimiter . $value . $return;
                $flag = true;
            }
        }

        $return = trim($return, $this->delimiter);
        unset($data['action']);

        if ($data) {
            return $return . '?' . self::httpBuildQuery($data);
        }

        return $return;
    }

    public static function httpBuildQuery(array $data)
    {
        return urldecode(http_build_query($data));
//        return http_build_query($data));
    }

    public function getDefault($name)
    {
        if (isset($this->defaults[$name])) {
            return $this->defaults[$name];
        }

        return null;
    }

    public function getRoute()
    {
        return $this->route;
    }
}