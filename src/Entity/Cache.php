<?php

namespace SNOWGIRL_CORE\Entity;

use SNOWGIRL_CORE\Entity;

class Cache extends Entity
{
    protected static $table = 'cache';
    protected static $pk = 'cache_id';

    protected static $columns = [
        'cache_id' => ['type' => self::COLUMN_INT, self::AUTO_INCREMENT],
        'key' => ['type' => self::COLUMN_TEXT],
        'value' => ['type' => self::COLUMN_TEXT],
        'created_at' => ['type' => self::COLUMN_TIME, self::REQUIRED],
        'updated_at' => ['type' => self::COLUMN_TIME, 'default' => null]
    ];

    public function setId($v): Entity
    {
        return $this->setCacheId($v);
    }

    public function getId(bool $makeCompositeId = true)
    {
        return $this->getCacheId();
    }

    public function setCacheId($v)
    {
        return $this->setRequiredAttr('cache_id', (int)$v);
    }

    public function getCacheId()
    {
        return (int)$this->getRawAttr('cache_id');
    }

    public function setKey($v)
    {
        return $this->setRequiredAttr('key', trim($v));
    }

    public function getKey()
    {
        return $this->getRawAttr('key');
    }

    public function setValue($v)
    {
        return $this->setRequiredAttr('value', trim($v));
    }

    public function getValue()
    {
        return $this->getRawAttr('value');
    }

    public function setCreatedAt($v)
    {
        return $this->setRawAttr('created_at', self::normalizeTime($v));
    }

    public function getCreatedAt($datetime = false)
    {
        return $datetime ? self::timeToDatetime($this->getRawAttr('created_at')) : $this->getRawAttr('created_at');
    }

    public function setUpdatedAt($v)
    {
        return $this->setRawAttr('updated_at', self::normalizeTime($v, true));
    }

    public function getUpdatedAt($datetime = false)
    {
        return $datetime ? self::timeToDatetime($this->getRawAttr('updated_at')) : $this->getRawAttr('updated_at');
    }
}