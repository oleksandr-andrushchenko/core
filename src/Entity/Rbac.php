<?php

namespace SNOWGIRL_CORE\Entity;

use SNOWGIRL_CORE\Entity;

class Rbac extends Entity
{
    protected static $table = 'rbac';
    protected static $pk = 'rbac_id';

    protected static $columns = [
        'rbac_id' => ['type' => self::COLUMN_INT, self::AUTO_INCREMENT],
        'role_id' => ['type' => self::COLUMN_INT, 'default' => null],
        'user_id' => ['type' => self::COLUMN_INT, 'default' => null, 'entity' => __NAMESPACE__ . '\User'],
        'permission_id' => ['type' => self::COLUMN_INT, 'default' => null],
        'created_at' => ['type' => self::COLUMN_TIME, self::REQUIRED],
        'updated_at' => ['type' => self::COLUMN_TIME, 'default' => null]
    ];

    public function setId($v)
    {
        return $this->setRbacId($v);
    }

    public function getId($makeCompositeId = true)
    {
        return $this->getRbacId();
    }

    public function setRbacId($v)
    {
        return $this->setRequiredAttr('rbac_id', (int)$v);
    }

    public function getRbacId()
    {
        return (int)$this->getRawAttr('rbac_id');
    }

    public function setRoleId(?int $roleId)
    {
        return $this->setRawAttr('role_id', (int)$roleId);
    }

    public function getRoleId(): ?int
    {
        return ($v = $this->getRawAttr('role_id')) ? (int)$v : null;
    }

    public function setUserId(?int $userId)
    {
        return $this->setRawAttr('user_id', (int)$userId);
    }

    public function getUserId(): ?int
    {
        return ($v = $this->getRawAttr('user_id')) ? (int)$v : null;
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