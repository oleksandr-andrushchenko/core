<?php

namespace SNOWGIRL_CORE\Entity;

use SNOWGIRL_CORE\Entity;

class Redirect extends Entity
{
    protected static $table = 'redirect';
    protected static $pk = 'redirect_id';

    protected static $columns = [
        'redirect_id' => ['type' => self::COLUMN_INT, self::AUTO_INCREMENT],
        'uri_from' => ['type' => self::COLUMN_TEXT, self::REQUIRED],
        'uri_to' => ['type' => self::COLUMN_TEXT, self::REQUIRED],
        'created_at' => ['type' => self::COLUMN_TIME, self::REQUIRED],
        'updated_at' => ['type' => self::COLUMN_TIME, 'default' => null]
    ];

    public function setId($v): Entity
    {
        return $this->setRedirectId($v);
    }

    public function getId(bool $makeCompositeId = true)
    {
        return $this->getRedirectId();
    }

    public function setRedirectId($v)
    {
        return $this->setRequiredAttr('redirect_id', (int)$v);
    }

    public function getRedirectId()
    {
        return (int)$this->getRawAttr('redirect_id');
    }

    /**
     * @param $v
     *
     * @return Entity
     * @throws EntityException
     */
    public function setUriFrom($v)
    {
        return $this->setRequiredAttr('uri_from', trim($v));
    }

    public function getUriFrom()
    {
        return $this->getRawAttr('uri_from');
    }

    public function setUriTo($v)
    {
        return $this->setRequiredAttr('uri_to', trim($v));
    }

    public function getUriTo()
    {
        return $this->getRawAttr('uri_to');
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