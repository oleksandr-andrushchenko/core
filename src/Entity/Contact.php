<?php

namespace SNOWGIRL_CORE\Entity;

use SNOWGIRL_CORE\Entity;

class Contact extends Entity
{
    protected static $table = 'contact';
    protected static $pk = 'contact_id';

    protected static $columns = [
        'contact_id' => ['type' => self::COLUMN_INT, self::AUTO_INCREMENT],
        'name' => ['type' => self::COLUMN_TEXT, self::REQUIRED],
        'email' => ['type' => self::COLUMN_TEXT, self::REQUIRED],
        'body' => ['type' => self::COLUMN_TEXT, self::REQUIRED],
        'created_at' => ['type' => self::COLUMN_TIME, self::REQUIRED],
        'updated_at' => ['type' => self::COLUMN_TIME, 'default' => null]
    ];

    public function setId($v)
    {
        return $this->setContactId($v);
    }

    public function getId($makeCompositeId = true)
    {
        return $this->getContactId();
    }

    public function setContactId($v)
    {
        return $this->setRequiredAttr('contact_id', (int)$v);
    }

    public function getContactId()
    {
        return (int)$this->getRawAttr('contact_id');
    }

    public function setName($v)
    {
        return $this->setRequiredAttr('name', trim($v));
    }

    public function getName()
    {
        return $this->getRawAttr('name');
    }

    public function setEmail($v)
    {
        return $this->setEmailAttr('email', trim($v));
    }

    public function getEmail()
    {
        return $this->getRawAttr('email');
    }

    public function setBody($v)
    {
        return $this->setRequiredAttr('body', trim($v));
    }

    public function getBody()
    {
        return $this->getRawAttr('body');
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