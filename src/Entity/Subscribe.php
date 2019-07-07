<?php

namespace SNOWGIRL_CORE\Entity;

use SNOWGIRL_CORE\Entity;

class Subscribe extends Entity
{
    protected static $table = 'subscribe';
    protected static $pk = 'subscribe_id';

    protected static $columns = [
        'subscribe_id' => ['type' => self::COLUMN_INT, self::AUTO_INCREMENT],
        'name' => ['type' => self::COLUMN_TEXT, self::REQUIRED],
        'email' => ['type' => self::COLUMN_TEXT, self::REQUIRED],
        'filter' => ['type' => self::COLUMN_TEXT, 'default' => ''],
        'code' => ['type' => self::COLUMN_TEXT, self::REQUIRED],
        'is_confirmed' => ['type' => self::COLUMN_INT, 'default' => 0],
        'is_active' => ['type' => self::COLUMN_INT, 'default' => 0],
        'created_at' => ['type' => self::COLUMN_TIME, self::REQUIRED],
        'updated_at' => ['type' => self::COLUMN_TIME, 'default' => null]
    ];

    public function setId($v)
    {
        return $this->setSubscribeId($v);
    }

    public function getId($makeCompositeId = true)
    {
        return $this->getSubscribeId();
    }

    public function setSubscribeId($v)
    {
        return $this->setRequiredAttr('subscribe_id', (int)$v);
    }

    public function getSubscribeId()
    {
        return (int)$this->getRawAttr('subscribe_id');
    }

    /**
     * @param $v
     *
     * @return Subscribe
     * @throws \SNOWGIRL_CORE\Exception\EntityAttr\Required
     */
    public function setName($v)
    {
        return $this->setRequiredAttr('name', trim($v));
    }

    public function getName()
    {
        return $this->getRawAttr('name');
    }

    /**
     * @param $v
     *
     * @return Subscribe
     * @throws \SNOWGIRL_CORE\Exception\EntityAttr\Email
     */
    public function setEmail($v)
    {
        return $this->setEmailAttr('email', trim($v));
    }

    public function getEmail()
    {
        return $this->getRawAttr('email');
    }

    public function setFilter($v)
    {
        return $this->setRawAttr('filter', self::normalizeJson($v));
    }

    public function getFilter($array = false)
    {
        $v = $this->getRawAttr('filter');
        return $array ? self::jsonToArray($v) : $v;
    }

    public function addFilter($k, $v)
    {
        $filter = $this->getFilter(true);
        $filter[$k] = $v;
        $this->setFilter($filter);
        return $this;
    }

    public function setCode($v)
    {
        return $this->setRequiredAttr('code', trim($v));
    }

    public function getCode()
    {
        return $this->getRawAttr('code');
    }

    /**
     * @param $v
     *
     * @return Subscribe
     */
    public function setIsConfirmed($v)
    {
        return $this->setRawAttr('is_confirmed', $v ? 1 : 0);
    }

    public function getIsConfirmed()
    {
        return (int)$this->getRawAttr('is_confirmed');
    }

    public function isConfirmed()
    {
        return 1 == $this->getIsConfirmed();
    }

    public function setIsActive($v)
    {
        return $this->setRawAttr('is_active', $v ? 1 : 0);
    }

    public function getIsActive()
    {
        return (int)$this->getRawAttr('is_active');
    }

    public function isActive()
    {
        return 1 == $this->getIsActive();
    }

    public function setCreatedAt($v)
    {
        return $this->setRawAttr('created_at', self::normalizeTime($v));
    }

    public function getCreatedAt($datetime = false)
    {
        $v = $this->getRawAttr('created_at');
        return $datetime ? self::timeToDatetime($v) : $v;
    }

    public function setUpdatedAt($v)
    {
        return $this->setRawAttr('updated_at', self::normalizeTime($v, true));
    }

    public function getUpdatedAt($datetime = false)
    {
        $v = $this->getRawAttr('updated_at');
        return $datetime ? self::timeToDatetime($v) : $v;
    }
}