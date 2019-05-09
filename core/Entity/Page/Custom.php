<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 2/2/18
 * Time: 12:27 AM
 */
namespace SNOWGIRL_CORE\Entity\Page;

use SNOWGIRL_CORE\Entity;

/**
 * Class Custom
 * @package SNOWGIRL_CORE\Entity\Page
 */
class Custom extends Entity
{
    protected static $table = 'page_custom';
    protected static $pk = 'page_custom_id';

    protected static $columns = [
        'page_custom_id' => ['type' => self::COLUMN_INT, self::AUTO_INCREMENT],
        'uri' => ['type' => self::COLUMN_TEXT, self::REQUIRED],
        'uri_hash' => ['type' => self::COLUMN_TEXT, self::REQUIRED],
        'name' => ['type' => self::COLUMN_TEXT],
        'meta_title' => ['type' => self::COLUMN_TEXT],
        'meta_description' => ['type' => self::COLUMN_TEXT],
        'meta_keywords' => ['type' => self::COLUMN_TEXT],
        'h1' => ['type' => self::COLUMN_TEXT, self::SEARCH_IN, self::SEARCH_DISPLAY],
        'body' => ['type' => self::COLUMN_TEXT],
        'is_active' => ['type' => self::COLUMN_INT, 'default' => 0],
        'created_at' => ['type' => self::COLUMN_TIME, self::REQUIRED],
        'updated_at' => ['type' => self::COLUMN_TIME, 'default' => null]
    ];

    public function setId($v)
    {
        return $this->setPageCustomId($v);
    }

    public function getId($makeCompositeId = true)
    {
        return $this->getPageCustomId();
    }

    public function setPageCustomId($v)
    {
        return $this->setRequiredAttr('page_custom_id', (int)$v);
    }

    public function getPageCustomId()
    {
        return (int)$this->getRawAttr('page_custom_id');
    }

    public function setUri($v)
    {
        return $this->setRequiredAttr('uri', trim(trim($v), '/'));
    }

    public function getUri()
    {
        return $this->getRawAttr('uri');
    }

    public function setUriHash($v)
    {
        return $this->setRequiredAttr('uri_hash', trim($v));
    }

    public function getUriHash()
    {
        return $this->getRawAttr('uri_hash');
    }

    public function setName($v)
    {
        return $this->setRawAttr('name', $v ? trim($v) : null);
    }

    public function getName()
    {
        return $this->getRawAttr('name');
    }

    public function setMetaTitle($v)
    {
        return $this->setRawAttr('meta_title', $v ? trim($v) : null);
    }

    public function getMetaTitle()
    {
        return $this->getRawAttr('meta_title');
    }

    public function setMetaDescription($v)
    {
        return $this->setRawAttr('meta_description', $v ? trim($v) : null);
    }

    public function getMetaDescription()
    {
        return $this->getRawAttr('meta_description');
    }

    public function setMetaKeywords($v)
    {
        return $this->setRawAttr('meta_keywords', $v ? trim($v) : null);
    }

    public function getMetaKeywords()
    {
        return $this->getRawAttr('meta_keywords');
    }

    public function setH1($v)
    {
        return $this->setRawAttr('h1', $v ? trim($v) : null);
    }

    public function getH1()
    {
        return $this->getRawAttr('h1');
    }

    public function setBody($v)
    {
        return $this->setRawAttr('body', $v ? trim($v) : null);
    }

    public function getBody()
    {
        return $this->getRawAttr('body');
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