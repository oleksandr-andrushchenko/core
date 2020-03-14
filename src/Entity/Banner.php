<?php

namespace SNOWGIRL_CORE\Entity;

use SNOWGIRL_CORE\AbstractApp;
use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\View\Widget\Carousel\ItemInterface;

class Banner extends Entity implements ItemInterface
{
    protected static $table = 'banner';
    protected static $pk = 'banner_id';

    protected static $columns = [
        'banner_id' => ['type' => self::COLUMN_INT, self::AUTO_INCREMENT],
        'image' => ['type' => self::COLUMN_TEXT, self::IMAGE, self::REQUIRED],
        'type' => ['type' => self::COLUMN_TEXT, self::REQUIRED],
        'caption' => ['type' => self::COLUMN_TEXT, 'default' => null],
        'link' => ['type' => self::COLUMN_TEXT, 'default' => null],
        'is_active' => ['type' => self::COLUMN_INT, 'default' => 0],
        'created_at' => ['type' => self::COLUMN_TIME, self::REQUIRED],
        'updated_at' => ['type' => self::COLUMN_TIME, 'default' => null]
    ];

    public function setId($v): Entity
    {
        return $this->setBannerId($v);
    }

    public function getId(bool $makeCompositeId = true)
    {
        return $this->getBannerId();
    }

    public function setBannerId($v)
    {
        return $this->setRequiredAttr('banner_id', (int)$v);
    }

    public function getBannerId()
    {
        return (int)$this->getRawAttr('banner_id');
    }

    public function setImage($hash)
    {
        return $this->setRequiredAttr('image', $hash);
    }

    public function getImage()
    {
        return $this->getRawAttr('image');
    }

    public function setCaption($caption)
    {
        return $this->setRawAttr('caption', ($caption = trim($caption)) ? $caption : null);
    }

    public function getCaption()
    {
        return $this->getRawAttr('caption');
    }

    public function setLink($link)
    {
        return $this->setRawAttr('link', ($link = trim($link)) ? $link : null);
    }

    public function getLink()
    {
        return $this->getRawAttr('link');
    }

    public function setType($type)
    {
        return $this->setRequiredAttr('type', $type);
    }

    public function getType()
    {
        return $this->getRawAttr('type');
    }

    public function setIsActive($v)
    {
        return $this->setRawAttr('is_active', $v ? 1 : 0);
    }

    public function getIsActive()
    {
        return 1 == $this->getRawAttr('is_active');
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

    public function getHref(AbstractApp $app)
    {
        return $this->getLink();
    }

    public function getImageHash()
    {
        return $this->getImage();
    }
}