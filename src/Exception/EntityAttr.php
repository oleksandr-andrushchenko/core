<?php

namespace SNOWGIRL_CORE\Exception;

use SNOWGIRL_CORE\Exception;

class EntityAttr extends Exception
{
    public function __construct($entity, $key, $value, $type = null)
    {
        parent::__construct($this->buildMessage($entity, $key, $value, $type));
    }

    protected function buildMessage($entity, $key, $value, $type)
    {
        return trans(
            'error.ex-' . ($type ?: $this->getTypeName()),
            is_object($entity) ? get_class($entity) : $entity,
            $key,
            $value
        );
    }

    protected function getTypeName()
    {
        $output = explode('\\', get_class($this));
        $output = lcfirst($output[count($output) - 1]);
        return $output;
    }
}