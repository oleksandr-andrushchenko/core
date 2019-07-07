<?php

namespace SNOWGIRL_CORE;

use SNOWGIRL_CORE\Manager\DataProvider;
use SNOWGIRL_CORE\Service\Logger;
use SNOWGIRL_CORE\Service\Storage;
use SNOWGIRL_CORE\Service\Cache;
use SNOWGIRL_CORE\Service\Storage\Query;
use SNOWGIRL_CORE\View\Widget\Form\Input\Tag as TagInput;
use SNOWGIRL_CORE\View\Widget\Form\Input\File\Image as ImageInput;
use SNOWGIRL_CORE\Helper\Arrays;
use SNOWGIRL_CORE\Service\Storage\Query\Expr;

/**
 * Class Manager
 *
 * @property App app
 * @package SNOWGIRL_CORE
 */
abstract class Manager
{
    public const STORAGE_RDBMS = 'rdbms';
    public const STORAGE_FTDBMS = 'ftdbms';
    public const STORAGE_NOSQL = 'nosql';

    /** @var App */
    protected $app;

    public function getApp()
    {
        return $this->app;
    }

    protected $masterServices = true;

    public function getMasterServices()
    {
        return $this->masterServices;
    }

    /** @var Entity */
    protected $entity;

    /** @var Query */
    protected $query;

    /**
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $entity = static::getEntityClass();
        $this->entity = new $entity();
        $this->clear();
    }

    public static function getEntityPk()
    {
        $m = static::getEntityClass();
        return $m::getPk();
    }

    protected static function getEntityTable()
    {
        $m = static::getEntityClass();
        return $m::getTable();
    }

    protected static function getEntityColumns()
    {
        $m = static::getEntityClass();
        return $m::getColumns();
    }

    public function clearReqResult()
    {
        $this->items = null;
        $this->foundRows = null;

        return $this;
    }

    /**
     * @param $k
     * @param $v
     *
     * @return Manager
     */
    public function setPropertyAndCheckCache($k, $v)
    {
        if (property_exists($this, $k)) {
            $this->$k = $v;
        } elseif ($v != $this->query->$k) {
            $this->query->$k = $v;
        }

        $this->clearReqResult();

        return $this;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function clearQuery()
    {
        $this->query = new Query;
        return $this;
    }

    /**
     * @return Manager
     */
    public function clear()
    {
        $this->clearQuery();
        $this->isCacheOrCacheKey = null;
        $this->clearReqResult();
        return $this;
    }

    protected $storage = self::STORAGE_RDBMS;

    /**
     * @param $storage
     *
     * @return $this|Manager
     */
    public function setStorage($storage)
    {
        return $this->setPropertyAndCheckCache('storage', $storage);
    }

    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @param $columns
     *
     * @return Manager
     */
    public function setColumns($columns)
    {
        return $this->setPropertyAndCheckCache('columns', $columns);
    }

    /**
     * @param mixed $v
     *
     * @return Manager
     */
    public function addColumn($v)
    {
        $this->query->columns = array_merge(Arrays::cast($this->query->columns), Arrays::cast($v));
        $this->clearReqResult();
        return $this;
    }

    /**
     * @param $joins
     *
     * @return Manager
     */
    public function setJoins($joins)
    {
        $joins = Arrays::cast($joins);

        foreach ($joins as $i => $options) {
            if (is_array($joins[$i])) {
                array_unshift($joins[$i], $this->entity->getTable());
            }
        }

        return $this->setPropertyAndCheckCache('joins', $joins);
    }

    /**
     * @param $v
     *
     * @return $this
     */
    public function addJoin($v)
    {
        if (is_array($v)) {
            array_unshift($v, $this->entity->getTable());
        }

        $this->query->joins = array_merge(Arrays::cast($this->query->joins), [$v]);
        $this->clearReqResult();
        return $this;
    }

    /**
     * @param $where
     *
     * @return Manager
     */
    public function setWhere($where)
    {
        return $this->setPropertyAndCheckCache('where', $where);
    }

    /**
     * @param array|Expr $v
     *
     * @return Manager
     */
    public function addWhere($v)
    {
        $this->query->where = array_merge(Arrays::cast($this->query->where), Arrays::cast($v));
        $this->clearReqResult();
        return $this;
    }

    /**
     * @param $indexes
     *
     * @return Manager
     */
    public function setIndexes($indexes)
    {
        return $this->setPropertyAndCheckCache('indexes', $indexes);
    }

    /**
     * @param $groups
     *
     * @return Manager
     */
    public function setGroups($groups)
    {
        return $this->setPropertyAndCheckCache('groups', $groups);
    }

    /**
     * @param $v
     *
     * @return Manager
     */
    public function addGroup($v)
    {
        $this->query->groups = array_merge(Arrays::cast($this->query->groups), Arrays::cast($v));
        $this->clearReqResult();
        return $this;
    }

    /**
     * @param $orders
     *
     * @return Manager
     */
    public function setOrders($orders)
    {
        return $this->setPropertyAndCheckCache('orders', $orders);
    }

    /**
     * @param array|Expr $order
     *
     * @return Manager
     */
    public function addOrder($order)
    {
        $this->query->orders = array_merge(Arrays::cast($this->query->orders), Arrays::cast($order));
        $this->clearReqResult();
        return $this;
    }

    public function setOffset($offset)
    {
        return $this->setPropertyAndCheckCache('offset', $offset);
    }

    /**
     * @param $limit
     *
     * @return Manager
     */
    public function setLimit($limit)
    {
        return $this->setPropertyAndCheckCache('limit', $limit);
    }

    /**
     * @param $v
     *
     * @return Manager
     */
    public function calcTotal($v)
    {
        $this->query->foundRows = !!$v;
        $this->clearReqResult();
        return $this;
    }

    protected $isCacheOrCacheKey;

    /**
     * @todo rename to setIdListAndPopulateCacheKey
     *
     * @param bool $isCacheOrCacheKey
     *
     * @return $this
     */
    public function cacheOutput($isCacheOrCacheKey = true)
    {
        $this->isCacheOrCacheKey = $isCacheOrCacheKey;
        $this->clearReqResult();
        return $this;
    }

    protected function isReqCache()
    {
        return null !== $this->items;
    }

    protected $storageObject;

    public function setStorageObject(Storage $storage)
    {
        $this->storageObject = $storage;
        return $this;
    }

    /**
     * @return Storage
     */
    public function getStorageObject()
    {
        return $this->storageObject ?: $this->app->services->{$this->storage}(null, null, $this->masterServices);
    }

    /**
     * @return Cache
     */
    public function getCacheObject()
    {
        return $this->app->services->mcms(null, null, $this->masterServices);
    }

    protected function getRawItems()
    {
        return $this->getStorageObject()->selectMany($this->entity->getTable(), $this->query);
    }

    protected function getRawFoundRows()
    {
        return $this->getStorageObject()->foundRows();
    }

    protected function getParams()
    {
        return $this->query->export();
    }

    protected function getCacheKey()
    {
        return is_string($this->isCacheOrCacheKey) ? $this->isCacheOrCacheKey : implode('-', [
            $this->entity->getTable(),
            'dyn',
            md5(serialize($this->getParams())),
//            'ids'
        ]);
    }

    /**
     * @return Manager
     */
    protected function exec()
    {
        if ($this->isReqCache()) {
            return $this;
        }

        if ($this->isCacheOrCacheKey && $this->getCacheObject()->isOn()) {
            $this->setColumns($this->entity->getPk());

            $k = $this->getCacheKey();

            if (false === ($r = $this->getCacheObject()->get($k))) {
                $r = [];
                $r[] = $this->getRawItems();

                if ($this->query->foundRows) {
                    $r[] = $this->getRawFoundRows();
                }

                $this->getCacheObject()->set($k, $r);
            }

            $pk = $this->entity->getPk();

            $this->items = $this->getByMcmsFindMany(array_map(function ($item) use ($pk) {
                return $item[$pk];
            }, $r[0]));

            if ($this->query->foundRows) {
                $this->foundRows = $r[1];
            }
        } else {
            $this->items = $this->getRawItems();

            if ($this->query->foundRows) {
                $this->foundRows = $this->getRawFoundRows();
            }
        }

        $this->clearQuery();

        return $this;
    }

    protected $items;

    public function getItems()
    {
        return $this->exec()->items;
    }

    protected $foundRows;

    public function getFoundRows()
    {
        return $this->exec()->foundRows;
    }

    public function getTotal()
    {
        return $this->getFoundRows();
    }

    //@todo cache
    public function getCount()
    {
        return $this->getStorageObject()
            ->selectCount($this->entity->getTable(), $this->query);
    }

    /**
     * @param null $idAsKeyOrKey
     *
     * @return array
     */
    public function getArrays($idAsKeyOrKey = null)
    {
        if ($idAsKeyOrKey) {
            $tmp = [];

            if (is_string($idAsKeyOrKey)) {
                $pk = $idAsKeyOrKey;
            } else {
                $pk = $this->entity->getPk();
            }

            foreach ($this->getItems() as $item) {
                $tmp[$item[$pk]] = $item;
            }

            return $tmp;
        }

        return $this->getItems();
    }

    /**
     * @return array|null
     */
    public function getArray()
    {
        if (!$this->isReqCache()) {
            $this->setLimit(1);
        }

        return ($tmp = $this->getArrays()) ? $tmp[0] : null;
    }

    /**
     * @param null $idAsKeyOrKey
     *
     * @return Entity[]
     */
    public function getObjects($idAsKeyOrKey = null)
    {
        return $this->populate($this->getArrays($idAsKeyOrKey));
    }

    /**
     * @return null|Entity
     * @throws \Exception
     */
    public function getObject()
    {
        if (!$this->isReqCache()) {
            $this->setLimit(1);
        }

        return ($tmp = $this->getObjects()) ? $tmp[0] : null;
    }

    public function getColumn($column, $columns = null)
    {
        return array_keys($this->setColumns($columns ?: $column)->getArrays($column));
    }

    public static function makeCompositePkId(Entity $entity)
    {
        return Entity::makeCompositePkIdFromPkIdArray(self::makePkIdArray($entity));
    }

    public static function makePkIdArray(Entity $entity)
    {
        return array_combine($entity->getPk(), array_map(function ($k) use ($entity) {
            return $entity->get($k);
        }, $entity->getPk()));
    }

    public static function makePkIdArrayById($pkId)
    {
        /** @var Entity $entityClass */
        $entityClass = static::getEntityClass();

        $deComposedPkId = $entityClass::isPkIdComposed($pkId) ? $entityClass::makePkIdArrayFromCompositePkId($pkId) : $pkId;

        return array_combine($entityClass::getPk(), $deComposedPkId);
    }

    protected function makeCacheFromObject(Entity $entity = null)
    {
        return is_object($entity) ? $entity->getAttrs() : null;
    }

    protected function makeObjectFromCache($cache)
    {
        return is_array($cache) ? $this->populateRow($cache) : null;
    }

    /**
     * @param string|int $id
     *
     * @return Entity
     */
    public function find($id)
    {
        $key = $this->getCacheKeyById($id);

        if (false !== ($output = $this->getCacheObject()->get($key))) {
            return self::makeObjectFromCache($output);
        }

        $cache = $this->getStorageObject()
            ->selectOne($this->entity->getTable(), new Query(['params' => [], 'where' => [
                $this->entity->getPk() => $this->entity->normalizeId($id)
            ]]));

        $this->getCacheObject()->set($key, $cache);

        return self::makeObjectFromCache($cache);
    }

    /**
     * @todo add mixed types support (arrays or objects)
     *
     * @param $id
     *
     * @return Entity
     */
    public function selectOne($id)
    {
        return $this->find($id);
    }

    public function selectMany($where = null)
    {
        return $this->selectByWhere($where);
    }

    public function findBy($column, $value)
    {
        if ($column == $this->entity->getPk()) {
            return $this->find($value);
        }

        $key = $this->getCacheKeyByColumnValue($column, $value);

        if (false !== ($output = $this->getCacheObject()->get($key))) {
            return self::makeObjectFromCache($output);
        }

        $cache = $this->getStorageObject()
            ->selectOne($this->entity->getTable(), new Query(['params' => [], 'where' => [$column => $value]]));

        $this->getCacheObject()->set($key, $cache);

        return self::makeObjectFromCache($cache);
    }

    protected function getByMcmsFindMany(array $id)
    {
        return $this->getCacheObject()->findMany($id, [$this, 'getCacheKeyById'], function (array $id) {
            $output = [];

            $id = array_map([$this->entity, 'normalizeId'], $id);
            $pk = $this->entity->getPk();

            $tmp = $this->getStorageObject()
                ->selectMany($this->entity->getTable(), new Query(['params' => [], 'where' => [$pk => $id]]));

            foreach ($tmp as $item) {
                $output[$item[$pk]] = $item;
            }

            return $output;
        });
    }

    /**
     * @param array $id
     *
     * @return Entity[]
     */
    public function findMany(array $id)
    {
        return array_map(function ($cache) {
            return self::makeObjectFromCache($cache);
        }, $this->getByMcmsFindMany(array_map([$this->entity, 'normalizeId'], $id)));
    }

    protected function getByMcmsFindManyBy($column, array $value)
    {
        return $this->getCacheObject()->findManyWithArgs($value, [$this, 'getCacheKeyByColumnValue'], function ($column, array $value) {
            return $this->copy(true)
                ->setWhere([$column => $value])
                ->getArrays($column);
        }, [$column]);
    }

    public function findManyBy($column, array $value)
    {
        if ($column == $this->entity->getPk()) {
            return $this->findMany($value);
        }

        return array_map(function ($cache) {
            return self::makeObjectFromCache($cache);
        }, $this->getByMcmsFindManyBy($column, $value));
    }

    public function getCacheKeyByColumnValue($column, $value)
    {
        return $this->entity->getTable() . '-' . $column . '-' . $value;
    }

    public function selectBy($column, $value)
    {
        return $this->populate($this->getStorageObject()
            ->selectMany($this->entity->getTable(), new Query(['params' => [], 'where' => [$column => $value]])));
    }

    public function selectByWhere(array $where = [])
    {
        return $this->populate($this->getStorageObject()
            ->selectMany($this->entity->getTable(), new Query(['params' => [], 'where' => $where])));
    }

    /**
     * @param array         $rows
     * @param \Closure|null $fn
     *
     * @return Entity[]
     */
    public function populate($rows, \Closure $fn = null)
    {
        $objects = [];
        $class = $this->entity->getClass();

        foreach ($rows as $k => $row) {
            /** @var Entity $object */
            $object = new $class($row);
            $objects[$k] = $object->isNew(false)->dropPrevAttrs();
            $fn && $fn($object, $row);
        }

        return $objects;
    }

    /**
     * @param array         $row
     * @param \Closure|null $fn
     *
     * @return Entity
     */
    public function populateRow(array $row, \Closure $fn = null)
    {
        $class = $this->entity->getClass();
        /** @var Entity $object */
        $object = new $class($row);
        $object->isNew(false)->dropPrevAttrs();
        $fn && $fn($object, $row);
        return $object;
    }

    /**
     * @todo cache...
     * @return string|Entity
     */
    public static function getEntityClass()
    {
        return str_replace('Manager', 'Entity', get_called_class());
    }

    /**
     * @return Entity
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @todo check required...
     *
     * @param \SNOWGIRL_CORE\Entity $entity
     *
     * @return bool
     */
    protected function onInsert(Entity $entity)
    {
        false && $entity;
        return true;
    }

    protected function onInserted(Entity $entity)
    {
        false && $entity;
        return true;
    }

    /**
     * @todo add mixed types support
     *
     * @param Entity     $entity
     * @param bool|false $ignore
     *
     * @return array|bool|int|mixed|null|DateTime|string
     */
    public function insertOne(Entity $entity, $ignore = false)
    {
        if (!$entity->isNew()) {
            return null;
        }

        if (!$this->onInsert($entity)) {
            return null;
        }

        if ($entity->hasAttr('created_at') && !$entity->changedAttr('created_at')) {
            $entity->setAttr('created_at', date('Y-m-d H:i:s'));
        }

        $values = $entity->getAttrs();

        if ($this->app->services->{$this->storage}->insertOne($entity->getTable(), $values, new Query(['params' => [], 'ignore' => $ignore])) > 0) {
//            if (array_key_exists('int_id', $entity->getColumns())) {
//                $entity->set('int_id', $this->app->services->{$this->storage}->getReqInsertedId());
//            } else {
            if (!is_array($entity->getPk())) {
                $entity->setId($this->getStorageObject()->insertedId());
            }
//            }

            $this->onInserted($entity);

            $entity->dropPrevAttrs();

            return $entity->getId();
        }

        return false;
    }

    //@todo add mixed types support
    public function insertMany(array $values, $ignore = false)
    {
        return $this->app->services->{$this->storage}->insertMany($this->entity->getTable(), $values, new Query([
            'params' => [],
            'ignore' => $ignore
        ]));
    }

    /**
     * @todo check required...
     *
     * @param \SNOWGIRL_CORE\Entity $entity
     *
     * @return bool
     */
    protected function onUpdate(Entity $entity)
    {
        false && $entity;
        return true;
    }

    protected function onUpdated(Entity $entity)
    {
        return $this->deleteCache($entity);
    }

    /**
     * @todo add mixed types support (arrays or objects)
     *
     * @param Entity $entity
     * @param bool   $ignore
     *
     * @return bool|null
     */
    public function updateOne(Entity $entity, $ignore = false)
    {
        if (!$entity->isAttrsChanged()) {
            return null;
        }

        if (!$this->onUpdate($entity)) {
            return null;
        }

        if ($entity->hasAttr('updated_at') && !$entity->changedAttr('updated_at')) {
            $entity->setAttr('updated_at', date('Y-m-d H:i:s'));
        }

        $values = [];

        foreach (array_keys($entity->getPrevAttrs()) as $k) {
            $values[$k] = $entity->getRawAttr($k);
        }

        if ($this->app->services->{$this->storage}->updateMany($entity->getTable(), $values, new Query([
                'params' => [],
                'where' => $entity->getPkWhere(true),
                'ignore' => $ignore
            ])) > 0) {
            $this->onUpdated($entity);

            return true;
        }

        return false;
    }

    /**
     * @todo add mixed types support (arrays or objects)
     *
     * @param array $values
     * @param null  $where
     * @param bool  $ignore
     *
     * @return int
     */
    public function updateMany(array $values, $where = null, $ignore = false)
    {
        return $this->app->services->{$this->storage}->updateMany($this->entity->getTable(), $values, new Query([
            'params' => [],
            'where' => $where,
            'ignore' => $ignore
        ]));
    }

    public function save(Entity $entity, $ignore = false)
    {
        return $entity->isNew() ? $this->insertOne($entity, $ignore) : $this->updateOne($entity, $ignore);
    }

    public function deleteCache(Entity $entity)
    {
        return $this->getCacheObject()->delete($this->getCacheKeyByEntity($entity));
    }

    protected function onDelete(Entity $entity)
    {
        false && $entity;
        return true;
    }

    protected function onDeleted(Entity $entity)
    {
        $output = $this->deleteCache($entity);
//        $output = $output && $this->getCacheObject()->delete($this->getAllIDsCacheKey());
        return $output;
    }

    /**
     * @todo add mixed types support (arrays or objects)
     *
     * @param \SNOWGIRL_CORE\Entity $entity
     *
     * @return bool
     */
    public function deleteOne(Entity $entity)
    {
        if (!$this->onDelete($entity)) {
            return null;
        }

        $req = new Query(['where' => $entity->getPkWhere()]);

        if ($this->getStorageObject()->deleteOne($entity->getTable(), $req)) {
            $this->onDeleted($entity);

            return true;
        }

        return false;
    }

    /**
     * @todo add mixed types support (arrays or objects)
     *
     * @param null $where
     *
     * @return mixed
     */
    public function deleteMany($where = null)
    {
        return $this->getStorageObject()
            ->deleteMany($this->entity->getTable(), new Query(['params' => [], 'where' => $where]));
    }

    /**
     * @return Entity[]
     * @throws \Exception
     */
    public function findAll()
    {
        if ($this->getCacheObject()->isOn()) {
            return $this->findMany($this->getAllIDs());
        }

        $k = $this->getAllIDsCacheKey();

        //get frontend cache
        if (false !== ($allIDs = $this->getCacheObject()->get($k))) {
            return $this->findMany($allIDs);
        }

        $all = $this->copy(true)->getArrays(true);

        //set frontend cache
        $this->getCacheObject()->set($k, array_keys($all));

        foreach ($all as $id => $item) {
            $this->getCacheObject()->set($this->getCacheKeyById($id), $item);
        }

        return $this->populate($all);
    }

    public function findAll2()
    {
        return $this->clear()
//            ->cacheOutput(true)
            ->cacheOutput($this->getAllIDsCacheKey())
            ->getObjects();
    }

    protected static function getCacheKeyByTableAndId($table, $id)
    {
        return $table . '-' . (int)$id;
    }

    public function getCacheKeyById($id)
    {
        return self::getCacheKeyByTableAndId($this->entity->getTable(), $id);
    }

    public function getCacheKeyByEntity(Entity $entity)
    {
        return self::getCacheKeyByTableAndId($entity->getTable(), $entity->getId());
    }

    /**
     * @todo optimize...
     *
     * @param null $key
     *
     * @return array
     */
    public function getList($key = null)
    {
        return array_keys($this->addColumn($key ?: $this->entity->getPk())->getArrays($key ?: true));
    }

    public function getIDs()
    {
        return $this->getList();
    }

    /**
     * @param array             $id
     * @param Entity[]|string[] $keyToEntity
     * @param \Closure|null     $fn
     *
     * @return Entity[]
     */
    public function populateList(array $id, array $keyToEntity = [], \Closure $fn = null)
    {
        /** @var Entity[] $objects */
        $objects = array_filter($this->findMany($id), function ($i) {
            return is_object($i);
        });

        foreach ($keyToEntity as $key => $entity) {
            if (is_string($entity)) {
                /** @var Entity $entity */
                $pk = $entity::getPk();

                $id = [];

                foreach ($objects as $object) {
                    if ($v = $object->get($pk)) {
                        $id[] = $v;
                    }
                }

                if ($id) {
                    $linkedObjects = $this->app->managers->getByEntityClass($entity)->findMany($id);

                    foreach ($objects as $object) {
                        $linkedObjectId = $object->get($key);

                        if (isset($linkedObjects[$linkedObjectId])) {
                            $object->setLinked($key, $linkedObjects[$linkedObjectId]);
                        }
                    }
                }
            } elseif (is_object($entity)) {
                foreach ($objects as $object) {
                    $object->setLinked($key, $entity);
                }
            }
        }

        $objects = array_values($objects);

        if ($fn) {
            array_walk($objects, $fn);
        }

        return $objects;
    }

    /**
     * @param Entity[] $entities
     *
     * @return Entity[]
     */
    public static function mapEntitiesAddPksAsKeys(array $entities)
    {
        return Arrays::mapByKeyMaker($entities, function ($entity) {
            /** @var Entity $entity */
            return $entity::getPk();
        });
    }

    /**
     * @param null       $name
     * @param bool|false $multiple
     * @param array      $params
     * @param View|null  $view
     *
     * @return TagInput
     */
    public function makeTagPicker($name = null, $multiple = false, array $params = [], View $view = null)
    {
        $searchBy = $this->findColumns(Entity::SEARCH_IN)[0];

        return $this->app->views->tagInput(array_merge([
//            'name' => $name && isset($columns[$name]) ? $name : $this->entity->getPk(),
            'name' => $name ?: $this->entity->getPk(),
            'multiple' => $multiple,
            'uri' => $this->app->router->makeLink('admin', [
                'action' => 'database',
                'table' => $this->entity->getTable(),
                'search_by' => $searchBy,
                'search_value' => TagInput::WILDCARD,
                'column_display' => $searchBy,
                'search_use_fulltext' => 1
            ])
        ], $params), $view);
    }

    /**
     * @param null       $name
     * @param bool|false $multiple
     * @param View|null  $view
     *
     * @return ImageInput
     */
    public function makeImagePicker($name = null, $multiple = false, View $view = null)
    {
        $columns = $this->entity->getColumns();
        //@todo inf not passed - find...
        $name = $name && isset($columns[$name]) ? $name : (isset($columns['image']) ? 'image' : $this->entity->getPk());

        return $this->app->views->imageInput([
            'name' => $name,
            'multiple' => $multiple,
            'required' => in_array(Entity::REQUIRED, $columns[$name])
        ], $view);
    }

    public function getColumnToId($column, array $where = null)
    {
        $output = [];

        foreach ($this->clear()
                     ->setColumns([$this->entity->getPk(), $column])
                     ->setWhere($where)
                     ->getObjects() as $item) {
            /** @var Entity $item */
            $output[$item->get($column)] = $item->getId();
        }

        return $output;
    }

    /**
     * @param Entity $entity
     * @param        $k
     * @param null   $v
     *
     * @return Manager
     */
    public function setLinked(Entity $entity, $k, $v = null)
    {
        $entity->setLinked($k, $v);
        return $this;
    }

    /**
     * @todo separate method for many-to-many entities...
     *
     * @param \SNOWGIRL_CORE\Entity $entity
     * @param                       $k
     *
     * @return Entity
     */
    public function getLinked(Entity $entity, $k)
    {
        $this->bindLinkedObjects([$entity], $k);
        return $entity->getLinked($k);
    }

    /**
     * @param Entity[] $entities
     * @param          $k
     *
     * @return Manager
     */
    public function bindLinkedObjects(array $entities, $k)
    {
        foreach ($entities as $entity) {
            if (false === $entity->getLinked($k)) {
                if ($v = $entity->get($k)) {
                    $v = $this->app->managers->getByEntityClass($entity->getColumns()[$k]['entity'])->findBy($k, $v);
                } else {
                    $v = null;
                }

                $entity->setLinked($k, $v);
            }
        }

        return $this;
    }

    /**
     * @param array|Entity[] $objects
     * @param                $entityOrMap - Possible:
     *                                    - User::class
     *                                    - [User::class, Contact::class]
     *                                    - ['user_id' => User::class]
     *                                    - [new User(), Contact::class, 'params_hash' => Catalog::class]
     *
     * @return $this
     */
    public function addLinkedObjects(array $objects, $entityOrMap)
    {
        /** @var Entity|array $entityOrMap */
        /** @var Entity $entity */

        $map = [];

        if (is_string($entityOrMap)) {
            $map[$entityOrMap::getPk()] = $entityOrMap;
        } elseif (is_array($entityOrMap)) {
            foreach ($entityOrMap as $key => $entity) {
                $map[is_string($key) ? $key : $entity::getPk()] = $entity;
            }
        }

        foreach ($map as $column => $entity) {
            if (is_string($entity)) {
                $values = [];

                foreach ($objects as $object) {
                    if (false === $object->getLinked($column)) {
                        if ($v = $object->get($column)) {
                            $values[] = $v;
                        }
                    }
                }

                if ($values) {
                    $linkedObjects = $this->app->managers->getByEntityClass($entity)->findManyBy($column, $values);

                    foreach ($objects as $object) {
                        $linkedObjectValue = $object->get($column);

                        if (isset($linkedObjects[$linkedObjectValue])) {
                            $object->setLinked($column, $linkedObjects[$linkedObjectValue]);
                        }
                    }
                }
            } elseif (is_object($entity)) {
                foreach ($objects as $object) {
                    $object->setLinked($column, $entity);
                }
            }
        }

        return $this;
    }

    /**
     * @param Entity $entity
     * @param string $key
     *
     * @return null|Image
     */
    public function getImage(Entity $entity, $key = 'image')
    {
        if ($v = $entity->get($key)) {
            return $this->app->images->get($v);
        }

        return null;
    }

    /**
     * Logic encapsulated in the Entity object
     *
     * @param string $key
     *
     * @return int|mixed|null|DateTime|string
     */
    public function getDateTime($key = 'created_at')
    {
        if ($v = $this->entity->get($key)) {
            if ($v instanceof DateTime) {
                return $v;
            }
        }

        return null;
    }

    public function getLink(Entity $entity, array $params = [], $domain = false)
    {
        return null;
    }

    /**
     * @param bool|false $clear
     *
     * @return Manager
     */
    public function copy($clear = false)
    {
        $new = clone $this;

        if ($clear) {
            $new->clear();
        }

        return $new;
    }

    protected $dataProvider;

    protected function getProviderClasses(): array
    {
        return [
            self::class
        ];
    }

    protected function getProviderKeys(): array
    {
        return [
            $this->entity->getTable(),
            $this->entity->getTable() . 's',
        ];
    }

    protected $dataProviderName;

    protected function getDataProviderName(): string
    {
        if (null === $this->dataProviderName) {
            $provider = 'mysql';
            $providers = $this->app->config->{'data.provider'};

            foreach ($this->getProviderKeys() as $providerKey) {
                if ($providers->$providerKey) {
                    $provider = $providers->$providerKey;
                    break;
                }
            }

            $this->dataProviderName = $provider;
        }

        return $this->dataProviderName;
    }

    protected function getRawDataProvider(string $provider): DataProvider
    {
        $class = null;

        foreach ($this->getProviderClasses() as $providerClass) {
            $tmp = $providerClass . '\\DataProvider\\' . ucfirst($provider);

            if (class_exists($tmp)) {
                $class = $tmp;
                break;
            }
        }

        if (!$class) {
            throw new Exception('undefined class provider class');
        }

        return new $class($this);
    }

    public function getDataProvider(string $forceProvider = null): DataProvider
    {
        $provider = $this->getDataProviderName();

        if ((null === $forceProvider) || ($forceProvider == $provider)) {
            if (null == $this->dataProvider) {
                $this->dataProvider = $this->getRawDataProvider($provider);
            }

            return $this->dataProvider;
        }

        return $this->getRawDataProvider($forceProvider);
    }

    /**
     * @param string      $query
     * @param bool        $prefix
     * @param string|null $forceProvider
     *
     * @return Entity[]
     */
    public function getObjectsByQuery(string $query, bool $prefix = false, string $forceProvider = null): array
    {
        return $this->populateList($this->getDataProvider($forceProvider)->getListByQuery($query, $prefix));
    }

    public function getCountByQuery(string $query, bool $prefix = false, string $forceProvider = null): int
    {
        return $this->getDataProvider($forceProvider)->getCountByQuery($query, $prefix);
    }

    /**
     * @param $query
     *
     * @return null|Entity
     * @throws \Exception
     */
    public function getObjectByQuery($query)
    {
        if ($tmp = $this->setLimit(1)->getObjectsByQuery($query)) {
            return $tmp[0];
        }

        return null;
    }

    public function getAllIDsCacheKey()
    {
        return $this->entity->getTable() . '-all-ids';
    }

    public function getAllIDs()
    {
        $k = $this->getAllIDsCacheKey();

        if (false !== ($output = $this->getCacheObject()->get($k))) {
            return $output;
        }

        $output = $this->copy(true)->getIDs();

        $this->getCacheObject()->set($k, $output);
        return $output;
    }

    public function isColumn($column, $filter)
    {
        return in_array($filter, $this->entity->getColumns()[$column]);
    }

    public function findColumns($filter)
    {
        return array_keys(array_filter($this->entity->getColumns(), function ($settings) use ($filter) {
            return in_array($filter, $settings);
        }));
    }

    public function findIndexes($where)
    {
        $output = [];
        $count = 0;

        $where = Arrays::cast($where);

        foreach ($this->entity->getIndexes() as $curIndex => $indexColumns) {
            foreach ($indexColumns as $k => $indexColumn) {
                if (isset($where[$indexColumn]) && (is_string($where[$indexColumn]) || is_numeric($where[$indexColumn]))) {
                    if ($k > $count) {
                        $output = [$curIndex];
                        $count = $k;
                    } elseif ($k == $count) {
                        $output[] = $curIndex;
                    }
                } else {
                    break;
                }
            }
        }

        return $output;
    }

    protected function log($msg, $type = Logger::TYPE_DEBUG)
    {
        return $this->app->services->logger->make('manager: ' . $msg, $type);
    }
}