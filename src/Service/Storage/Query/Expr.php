<?php

namespace SNOWGIRL_CORE\Service\Storage\Query;

class Expr
{
    protected $text;
    protected $params;

    /**
     * @param $query
     */
    public function __construct($query)
    {
        $args = func_get_args();
        $this->text = (string)array_shift($args);
        $this->params = $args;
    }

    public function setQuery($v)
    {
        $this->text = $v;
        return $this;
    }

    public function getQuery()
    {
        return $this->text;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function addTo(array &$where, array &$params = [], $brackets = false)
    {
        if ($brackets) {
            $query = '(' . $this->getQuery() . ')';
        } else {
            $query = $this->getQuery();
        }

        $where[] = $query;

        $params = array_merge($params, $this->getParams());

        return $this;
    }

    public function __toString()
    {
        return 'QE[' . $this->text . ']+[' . implode(', ', $this->params) . ']';
    }
}