<?php

namespace SNOWGIRL_CORE;

class Query
{
    public $text;
    public $schema;
    public $columns;
    public $from;
    public $joins;
    public $where;
    public $groups;
    public $orders;
    public $offset;
    public $limit;
    public $havings;
    public $indexes;
    public $foundRows;
    public $params;
    public $options;
    public $ignore;
    public $placeholders = true;
    public $log = true;

    /**
     * @param $param
     *
     * @return Query
     */
    public static function normalize($param)
    {
        if (is_string($param)) {
            return new static(['text' => $param]);
        }

        if (is_array($param)) {
            return new static($param);
        }

        if ($param instanceof Query) {
            return $param;
        }

        return new Query();
    }

    public function __construct(array $params = [])
    {
        $this->merge($params);
    }

    public function __get($k)
    {
        return null;
    }

    public function clear()
    {
        foreach ($this as $k => $v) {
            unset($this->$k);
        }

        return $this;
    }

    public function merge(array $params)
    {
        foreach ($params as $k => $v) {
            $this->$k = $v;
        }

        return $this;
    }

    public function export()
    {
        $tmp = [];

        foreach ($this as $k => $v) {
            if (null !== $v) {
                $tmp[$k] = $v;
            }
        }

        return $tmp;
    }

    public function __toString()
    {
        return json_encode($this->export());
    }
}