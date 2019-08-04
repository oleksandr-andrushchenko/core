<?php

namespace SNOWGIRL_CORE\Entity;

use SNOWGIRL_CORE\Entity;

class User extends Entity implements UserInterface
{
    protected static $table = 'user';
    protected static $pk = 'user_id';

    protected static $columns = [
        'user_id' => ['type' => self::COLUMN_INT, self::AUTO_INCREMENT],
        'login' => ['type' => self::COLUMN_TEXT, self::REQUIRED, self::SEARCH_IN, self::SEARCH_DISPLAY],
        'password' => ['type' => self::COLUMN_TEXT, self::REQUIRED],
        'role_id' => ['type' => self::COLUMN_INT],
        'created_at' => ['type' => self::COLUMN_TIME, self::REQUIRED],
        'updated_at' => ['type' => self::COLUMN_TIME, 'default' => null]
    ];

    public function setId($v)
    {
        return $this->setUserId($v);
    }

    public function getId($makeCompositeId = true)
    {
        return $this->getUserId();
    }

    public function setUserId($v)
    {
        return $this->setRequiredAttr('user_id', (int)$v);
    }

    public function getUserId()
    {
        return (int)$this->getRawAttr('user_id');
    }

    public function setLogin($v)
    {
        return $this->setRequiredAttr('login', self::prepareLogin(trim($v)));
    }

    public function getLogin()
    {
        return $this->getRawAttr('login');
    }

    public function setPassword($v)
    {
        return $this->setRequiredAttr('password', md5($v));
    }

    public function getPassword()
    {
        return $this->getRawAttr('password');
    }

    public static function prepareLogin($v)
    {
        return preg_replace('/[^a-z0-9_]/', '_', strtolower($v));
    }

    public function setRoleId($v)
    {
        return $this->setRawAttr('role_id', (int)$v);
    }

    public function getRoleId()
    {
        return (int)$this->getRawAttr('role_id');
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