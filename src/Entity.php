<?php

namespace SNOWGIRL_CORE;

use SNOWGIRL_CORE\Exception\EntityAttr\Email as EmailAttrException;
use SNOWGIRL_CORE\Exception\EntityAttr\Required as RequiredAttrException;
use SNOWGIRL_CORE\Helper\Data as DataHelper;
use SNOWGIRL_CORE\Helper\Arrays;

abstract class Entity
{
    public const REQUIRED = 1;
    public const AUTO_INCREMENT = 2;

    public const COLUMN_INT = 3;
    public const COLUMN_FLOAT = 4;
    public const COLUMN_TIME = 5;
    public const COLUMN_TEXT = 6;
    public const COLUMN_ARRAY = 7;

    public const FTDBMS_FIELD = 8;
    public const FTDBMS_ATTR = 9;

    //virtual types
    public const BOOL = 10;
    public const IMAGE = 11;
    public const JSON = 12;
    public const MD5 = 13;

    public const SEARCH_IN = 14;
    public const SEARCH_DISPLAY = 15;

    protected static $table = 'table';
    protected static $pk = 'id';
    protected static $isFtdbmsIndex = false;
    protected static $columns = [];
    protected static $indexes = [];

    protected $attrs = [];
    protected $vars = [];

    protected $isNew = true;
    protected $prevAttrs = [];

    /**
     * Entity constructor.
     *
     * @param array $data
     */
    final public function __construct(array $data = [])
    {
        $columns = $this->getColumns();

        foreach ($data as $k => $v) {
            if (isset($columns[$k])) {
                $this->attrs[$k] = $v;
            } else {
                $this->vars[$k] = $v;
            }
        }
    }

    public static function getTable()
    {
        return static::$table;
    }

    /**
     * @return string|array
     */
    public static function getPk()
    {
        return static::$pk;
    }

    public static function isFtdbmsIndex()
    {
        return static::$isFtdbmsIndex;
    }

    public static function getColumns()
    {
        return static::$columns;
    }

    public static function getIndexes()
    {
        return static::$indexes;
    }

    public static function getClass()
    {
        return static::class;
    }

    /**
     * @param $k
     * @param $v
     *
     * @return $this|Entity
     */
    public function set($k, $v)
    {
        $method = 'set' . implode('', explode(' ', ucwords(str_replace('_', ' ', $k))));

        if (method_exists($this, $method)) {
            $this->$method($v);
        } elseif (array_key_exists($k, $this->getColumns())) {
            $this->setRawAttr($k, $v);
        } else {
            $this->setRawVar($k, $v);
        }

        return $this;
    }

    public function __set($k, $v)
    {
        return $this->set($k, $v);
    }

    /**
     * @param $k
     *
     * @return int|mixed|null|string|DateTime
     */
    public function get($k)
    {
        $method = 'get' . implode('', explode(' ', ucwords(str_replace('_', ' ', $k))));

        if (method_exists($this, $method)) {
            return $this->$method();
        } elseif (array_key_exists($k, $this->attrs)) {
            return $this->getRawAttr($k);
        } elseif (array_key_exists($k, $this->vars)) {
            return $this->getRawVar($k);
        }

        return null;
    }

    public function __get($k)
    {
        return $this->get($k);
    }

    public function setRawAttr($k, $v)
    {
        if ((!array_key_exists($k, $this->attrs)) || ($this->attrs[$k] != $v)) {
            $this->prevAttrs[$k] = $this->attrs[$k] ?? ($this->getColumns()[$k]['default'] ?? null);
        }

        //@todo fix in case of array...
        if ($k === $this->getPk()) {
            $this->isNew = false;
        }

        $this->attrs[$k] = $v;

        return $this;
    }

    public function setAttr($attr, $v)
    {
        return $this->setRawAttr($attr, $v);
    }

    public function getRawAttr($k)
    {
        return $this->attrs[$k] ?? null;
    }

    public function setRawVar($k, $v)
    {
        $this->vars[$k] = $v;
        return $this;
    }

    public function getRawVar($k)
    {
        return $this->vars[$k] ?? null;
    }

    /**
     * @param $k
     * @param $v
     *
     * @return Entity
     * @throws EmailAttrException
     */
    protected function setEmailAttr($k, $v)
    {
        if ($v && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
            throw new EmailAttrException($this, $k, $v);
        }

        return $this->setRawAttr($k, $v);
    }

    /**
     * @param $k
     * @param $v
     *
     * @return Entity
     * @throws RequiredAttrException
     */
    protected function setRequiredAttr($k, $v)
    {
        if (!$v) {
            throw new RequiredAttrException($this, $k, $v);
        }

        return $this->setRawAttr($k, $v);
    }

    public static function normalizePhone($input, $null = false)
    {
        $input = trim($input);

        if (!$input) {
            return $null ? null : '';
        }

        $input = preg_replace('/[^\d]/', '', $input);

        if (!$input) {
            return $null ? null : '';
        }

        return $input;
    }

    public static function normalizeText($input, $null = false)
    {
        $input = trim($input);

        if (!$input) {
            return $null ? null : '';
        }

        $input = DataHelper::normalizeText(trim($input));

        if (!$input) {
            return $null ? null : '';
        }

        return $input;
    }

    public static function normalizeUri($input, $null = false)
    {
        $input = trim(trim(trim($input), '/'));

        if (!$input) {
            return $null ? null : '';
        }

        $input = DataHelper::normalizeUri2(trim($input));

        if (!$input) {
            return $null ? null : '';
        }

        return $input;
    }

    public static function normalizeHash($input, $null = false)
    {
        $input = trim($input);

        if (!$input) {
            return $null ? null : '';
        }

        if (preg_match('/^[a-f0-9]{32}$/', $input)) {
            return $input;
        }

        return md5($input);
    }

    public static function normalizeInt($input, $null = false)
    {
        $input = trim($input);

        if (!$input) {
            return $null ? null : 0;
        }

        $input = (int)$input;

        if (!$input) {
            return $null ? null : 0;
        }

        return $input;
    }

    public static function normalizeJson($input, $null = false)
    {
        if (is_string($input)) {
            $input = trim($input);
            json_decode($input);

            if (JSON_ERROR_NONE == json_last_error()) {
                return $input;
            }

            if (!$input) {
                return $null ? null : '';
            }

            $input = [$input];
        }

        if (!$input) {
            return $null ? null : '';
        }

        if (is_array($input)) {
            return json_encode($input);
        }

        return $input;
    }

    public static function jsonToArray($input)
    {
        return $input ? json_decode($input, true) : [];
    }

    public static function normalizeTime($input, $null = false)
    {
        $format = 'Y-m-d H:i:s';

        if (is_numeric($input)) {
            $input = (new DateTime)->setTimestamp($input)->format($format);
        }

        if (is_string($input)) {
            $input = trim($input);
        }

        if ($input instanceof DateTime) {
            $input = $input->format($format);
        }

        if (!$input) {
            return $null ? null : '';
        }

        //validation
        $date = DateTime::createFromFormat($format, $input);

        if ($date && $date->format($format) == $input) {
            return $input;
        }

        return $null ? null : '';
    }

    public static function timeToDatetime($input)
    {
        if (is_numeric($input)) {
            $tmp = new DateTime();
            $tmp->setTimestamp((int)$input);
            return $tmp;
        }

        return new DateTime($input);
    }

    public static function normalizeBool($input, $null = false)
    {
        if ($null && (null === $input)) {
            return null;
        }

        $input = !!$input;

        return $input ? 1 : 0;
    }

    public static function normalizeId($id)
    {
        return (int)$id;
    }

    public function getAttrs()
    {
        return $this->attrs;
    }

    public function getVars()
    {
        return $this->vars;
    }

    public function setId($v)
    {
        return $this->set($this->getPk(), $v);
    }

    public static function makeCompositePkIdFromPkIdArray(array $id)
    {
        return implode('-', $id);
    }

    public static function makePkIdArrayFromCompositePkId($id)
    {
        return explode('-', $id);
    }

    public static function isPkIdComposed($id)
    {
        return is_array($id) ? false : true;
    }

    public function getId($makeCompositeId = true)
    {
        $pk = $this->getPk();

        if (is_array($pk)) {
            $tmp = [];

            foreach ($pk as $k) {
                $tmp[] = $this->get($k);
            }

            return $makeCompositeId ? self::makeCompositePkIdFromPkIdArray($tmp) : $tmp;
        }

        return $this->get($pk);
    }

    public function getPkWhere($prevAttr = false)
    {
        $output = [];

        $pk = $this->getPk();

        if (is_array($pk)) {
            foreach ($pk as $k) {
                $output[$k] = $prevAttr && null !== ($v = $this->getPrevAttr($k)) ? $v : $this->get($k);
            }
        } else {
            $output[$pk] = $prevAttr && null !== ($v = $this->getPrevAttr($pk)) ? $v : $this->getId();
        }

        return $output;
    }

    public function isAttrsChanged()
    {
        return count($this->prevAttrs) > 0;
    }

    public function isAttrChanged($k)
    {
        return array_key_exists($k, $this->prevAttrs);
    }

    public function changedAttr($attr)
    {
        return $this->isAttrChanged($attr);
    }

    public function hasAttr($attr)
    {
        return array_key_exists($attr, $this->getColumns());
    }

    public function issetAttr($attr)
    {
        return Arrays::_isset($attr, $this->attrs);
    }

    public function isRequiredAttr($attr)
    {
        return $this->hasAttr($attr) && in_array(self::REQUIRED, $this->getColumns()[$attr]);
    }

    public function getPrevAttrs()
    {
        return $this->prevAttrs;
    }

    public function getPrevAttr($k)
    {
        return $this->prevAttrs[$k] ?? null;
    }

    public function dropPrevAttrs()
    {
        $this->prevAttrs = [];
        return $this;
    }

    public function isNew($v = null)
    {
        if (null === $v) {
            return $this->isNew;
        }

        $this->isNew = !!$v;
        return $this;
    }

    protected $linked = [];

    /**
     * @todo to use this - need to call Manager::setLinkedObject first
     * @todo ...or use Manager::getLinked directly
     *
     * @param $k
     *
     * @return bool|Entity
     */
    public function getLinked($k)
    {
        if (isset($this->linked[$k])) {
            return $this->linked[$k];
        }

        return false;
    }

    /**
     * @param                      $k
     * @param null|Entity|Entity[] $v
     *
     * @return $this
     */
    public function setLinked($k, $v = null)
    {
        $this->linked[$k] = $v;
        return $this;
    }

    public function toArray()
    {
        return array_merge($this->getAttrs(), $this->getVars());
    }

    public function stringifyPrepare($template = null)
    {
    }

    public function __sleep()
    {
        return ['attrs'];
    }
}
