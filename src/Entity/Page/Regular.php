<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 10/4/16
 * Time: 11:15 PM
 */

namespace SNOWGIRL_CORE\Entity\Page;

use SNOWGIRL_CORE\DateTime;
use SNOWGIRL_CORE\Entity;

/**
 * Class Regular
 * @property int page_regular_id
 * @property string key
 * @property string meta_title
 * @property string meta_description
 * @property string meta_keywords
 * @property string menu_title
 * @property string title
 * @property string description
 * @property int rating
 * @property DateTime created_at
 * @property DateTime updated_at
 * @method static Regular factory()
 * @method \SNOWGIRL_CORE\Manager\Page\Regular getManager()
 * @package SNOWGIRL_CORE\Entity\Page
 */
class Regular extends Entity
{
    protected static $table = 'page_regular';
    protected static $pk = 'page_regular_id';

    protected static $columns = [
        'page_regular_id' => ['type' => self::COLUMN_INT, self::AUTO_INCREMENT],
        'key' => ['type' => self::COLUMN_TEXT, self::REQUIRED],
        'meta_title' => ['type' => self::COLUMN_TEXT],
        'meta_description' => ['type' => self::COLUMN_TEXT],
        'meta_keywords' => ['type' => self::COLUMN_TEXT],
        'menu_title' => ['type' => self::COLUMN_TEXT, 'default' => null],
        'h1' => ['type' => self::COLUMN_TEXT, self::SEARCH_IN, self::SEARCH_DISPLAY],
        'description' => ['type' => self::COLUMN_TEXT],
        'rating' => ['type' => self::COLUMN_INT, 'default' => 0],
        'is_menu' => ['type' => self::COLUMN_INT, 'default' => 0],
        'created_at' => ['type' => self::COLUMN_TIME, self::REQUIRED],
        'updated_at' => ['type' => self::COLUMN_TIME, 'default' => null]
    ];

    public function setId($v)
    {
        return $this->setPageRegularId($v);
    }

    public function getId($makeCompositeId = true)
    {
        return $this->getPageRegularId();
    }

    public function setPageRegularId($v)
    {
        return $this->setRequiredAttr('page_regular_id', (int)$v);
    }

    public function getPageRegularId()
    {
        return (int)$this->getRawAttr('page_regular_id');
    }

    public function setKey($v)
    {
        return $this->setRequiredAttr('key', trim($v));
    }

    public function getKey()
    {
        return $this->getRawAttr('key');
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

    public function setDescription($v)
    {
        return $this->setRawAttr('description', $v ? trim($v) : null);
    }

    public function getDescription()
    {
        return $this->getRawAttr('description');
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

    public function setRating($v)
    {
        return $this->setRawAttr('rating', (int)$v);
    }

    public function getRating()
    {
        return (int)$this->getRawAttr('rating');
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
