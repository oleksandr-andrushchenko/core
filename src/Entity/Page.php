<?php

namespace SNOWGIRL_CORE\Entity;

use SNOWGIRL_CORE\Entity;

class Page extends Entity
{
    protected static $table = 'page';
    protected static $pk = 'page_id';

    protected static $columns = [
        'page_id' => ['type' => self::COLUMN_INT, self::AUTO_INCREMENT],
        'key' => ['type' => self::COLUMN_TEXT, self::REQUIRED],
        'uri' => ['type' => self::COLUMN_TEXT, self::REQUIRED],
        'uri_hash' => ['type' => self::COLUMN_TEXT, self::REQUIRED],
        'name' => ['type' => self::COLUMN_TEXT],
        'meta_title' => ['type' => self::COLUMN_TEXT],
        'meta_description' => ['type' => self::COLUMN_TEXT],
        'meta_keywords' => ['type' => self::COLUMN_TEXT],
        'menu_title' => ['type' => self::COLUMN_TEXT, 'default' => null],
        'h1' => ['type' => self::COLUMN_TEXT, self::SEARCH_IN, self::SEARCH_DISPLAY],
        'body' => ['type' => self::COLUMN_TEXT],
        'description' => ['type' => self::COLUMN_TEXT],
        'rating' => ['type' => self::COLUMN_INT, 'default' => 0],
        'is_menu' => ['type' => self::COLUMN_INT, 'default' => 0],
        'is_suggestion' => ['type' => self::COLUMN_INT, 'default' => 0],
        'is_active' => ['type' => self::COLUMN_INT, 'default' => 0],
        'created_at' => ['type' => self::COLUMN_TIME, self::REQUIRED],
        'updated_at' => ['type' => self::COLUMN_TIME, 'default' => null]
    ];

    public function setId($v): Entity
    {
        return $this->setPageId($v);
    }

    public function getId(bool $makeCompositeId = true)
    {
        return $this->getPageId();
    }

    public function setPageId($v)
    {
        return $this->setRequiredAttr('page_id', (int)$v);
    }

    public function getPageId()
    {
        return (int)$this->getRawAttr('page_id');
    }

    public function setKey($v)
    {
        return $this->setRequiredAttr('key', trim($v));
    }

    public function getKey()
    {
        return $this->getRawAttr('key');
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

    public function setMenuTitle($v)
    {
        return $this->setRawAttr('menu_title', ($v = trim($v)) ? $v : null);
    }

    public function getMenuTitle()
    {
        return ($v = $this->getRawAttr('menu_title')) ? $v : null;
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

    public function setDescription($v)
    {
        return $this->setRawAttr('description', $v ? trim($v) : null);
    }

    public function getDescription()
    {
        return $this->getRawAttr('description');
    }

    public function setRating($v)
    {
        return $this->setRawAttr('rating', (int)$v);
    }

    public function getRating()
    {
        return (int)$this->getRawAttr('rating');
    }

    public function setIsMenu($v)
    {
        return $this->setRawAttr('is_menu', $v ? 1 : 0);
    }

    public function getIsMenu()
    {
        return (int)$this->getRawAttr('is_menu');
    }

    public function isMenu()
    {
        return 1 == $this->getIsMenu();
    }

    public function setIsSuggestion($v)
    {
        return $this->setRawAttr('is_suggestion', $v ? 1 : 0);
    }

    public function getIsSuggestion()
    {
        return (int)$this->getRawAttr('is_suggestion');
    }

    public function isSuggestion()
    {
        return 1 == $this->getIsSuggestion();
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

    public function make($attr, $default = null, array $params = [])
    {
        $tmp = $this->get($attr);

        if (!$tmp) {
            $tmp = $default;
        }

        if ($params) {
            return str_replace(array_map(function ($i) {
                return '{' . $i . '}';
            }, array_keys($params)), array_values($params), $tmp);
        }

        return $tmp;
    }
}
