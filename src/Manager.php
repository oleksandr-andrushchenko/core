<?php

namespace SNOWGIRL_CORE;

use SNOWGIRL_CORE\Cache\CacheInterface;
use SNOWGIRL_CORE\Db\DbInterface;
use SNOWGIRL_CORE\Indexer\IndexerInterface;
use SNOWGIRL_CORE\Manager\DataProvider;
use SNOWGIRL_CORE\Cache\NullCache;
use SNOWGIRL_CORE\View\Widget\Form\Input\Tag as TagInput;
use SNOWGIRL_CORE\View\Widget\Form\Input\File\Image as ImageInput;
use SNOWGIRL_CORE\Helper\Arrays;
use DateTime;

abstract class Manager
{
    /**
     * @var AbstractApp
     */
    protected $app;

    protected $masterServices = true;

    /**
     * @var Entity
     */
    protected $entity;

    /**
     * @var Query
     */
    protected $query;
    protected $isCacheOrCacheKey;
    protected $items;
    protected $foundRows;

    protected $dataProvider;
    protected $dataProviderName;
    private $db;


    /**
     * @param AbstractApp $app
     */
    public function __construct(AbstractApp $app)
    {
        $this->app = $app;
        $entity = static::getEntityClass();
        $this->entity = new $entity();
        $this->clear();
    }

    public function getApp()
    {
        return $this->app;
    }

    public function getMasterServices()
    {
        return $this->masterServices;
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

    public function clearReqResult(): Manager
    {
        $this->items = null;
        $this->foundRows = null;

        return $this;
    }

    public function setPropertyAndCheckCache($k, $v): Manager
    {
        if (property_exists($this, $k) && $v !== $this->$k) {
            $this->$k = $v;
        } elseif (property_exists($this->query, $k) && $v !== $this->query->$k) {
            $this->query->$k = $v;
        }

        $this->clearReqResult();

        return $this;
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    public function clearQuery(): Manager
    {
        $this->query = new Query;

        return $this;
    }

    public function clear(): Manager
    {
        $this->clearQuery();
        $this->isCacheOrCacheKey = null;
        $this->clearReqResult();

        return $this;
    }

    public function setColumns($columns): Manager
    {
        return $this->setPropertyAndCheckCache('columns', $columns);
    }

    public function addColumn($v): Manager
    {
        $this->query->columns = array_merge(Arrays::cast($this->query->columns), Arrays::cast($v));
        $this->clearReqResult();

        return $this;
    }

    public function setJoins($joins): Manager
    {
        $joins = Arrays::cast($joins);

        foreach ($joins as $i => $options) {
            if (is_array($joins[$i])) {
                array_unshift($joins[$i], $this->entity->getTable());
            }
        }

        return $this->setPropertyAndCheckCache('joins', $joins);
    }

    public function addJoin($v): Manager
    {
        if (is_array($v)) {
            array_unshift($v, $this->entity->getTable());
        }

        $this->query->joins = array_merge(Arrays::cast($this->query->joins), [$v]);
        $this->clearReqResult();

        return $this;
    }

    public function setWhere($where): Manager
    {
        return $this->setPropertyAndCheckCache('where', $where);
    }

    public function addWhere($v): Manager
    {
        $this->query->where = array_merge(Arrays::cast($this->query->where), Arrays::cast($v));
        $this->clearReqResult();

        return $this;
    }

    public function setIndexes($indexes): Manager
    {
        return $this->setPropertyAndCheckCache('indexes', $indexes);
    }

    public function setGroups($groups): Manager
    {
        return $this->setPropertyAndCheckCache('groups', $groups);
    }

    public function addGroup($v): Manager
    {
        $this->query->groups = array_merge(Arrays::cast($this->query->groups), Arrays::cast($v));
        $this->clearReqResult();

        return $this;
    }

    public function setOrders($orders): Manager
    {
        return $this->setPropertyAndCheckCache('orders', $orders);
    }

    public function addOrder($order): Manager
    {
        $this->query->orders = array_merge(Arrays::cast($this->query->orders), Arrays::cast($order));
        $this->clearReqResult();

        return $this;
    }

    public function setOffset($offset): Manager
    {
        return $this->setPropertyAndCheckCache('offset', $offset);
    }

    public function setLimit($limit): Manager
    {
        return $this->setPropertyAndCheckCache('limit', $limit);
    }

    public function setHavings($havings): Manager
    {
        return $this->setPropertyAndCheckCache('havings', $havings);
    }

    public function addHaving($v): Manager
    {
        $this->query->havings = array_merge(Arrays::cast($this->query->havings), Arrays::cast($v));
        $this->clearReqResult();

        return $this;
    }

    public function calcTotal($v): Manager
    {
        $this->query->foundRows = !!$v;
        $this->clearReqResult();

        return $this;
    }

    /**
     * @todo rename to setIdListAndPopulateCacheKey
     * @param bool $isCacheOrCacheKey
     * @return Manager
     */
    public function cacheOutput($isCacheOrCacheKey = true): Manager
    {
        $this->isCacheOrCacheKey = $isCacheOrCacheKey;
        $this->clearReqResult();

        return $this;
    }

    protected function isReqCache(): bool
    {
        return null !== $this->items;
    }

    public function setDb(DbInterface $db): Manager
    {
        $this->db = $db;

        return $this;
    }

    public function getDb(): DbInterface
    {
        return $this->db ?: $this->db = $this->app->container->db($this->masterServices);
    }

    public function getCache(): CacheInterface
    {
        return $this->app->container->cache($this->masterServices);
    }

    public function getIndexer(): IndexerInterface
    {
        return $this->app->container->indexer($this->masterServices);
    }

    protected function getRawItems()
    {
        return $this->getDb()->selectMany($this->entity->getTable(), $this->query);
    }

    protected function getRawFoundRows()
    {
        return $this->getDb()->foundRows();
    }

    protected function getParams()
    {
        return $this->query->export();
    }

    protected function getCacheKey(): string
    {
        return is_string($this->isCacheOrCacheKey) ? $this->isCacheOrCacheKey : implode('-', [
            $this->entity->getTable(),
            'dyn',
            md5(serialize($this->getParams())),
//            'ids'
        ]);
    }

    protected function exec(): Manager
    {
        if ($this->isReqCache()) {
            return $this;
        }

        if ($this->isCacheOrCacheKey && !$this->getCache() instanceof NullCache) {
            $this->setColumns($this->entity->getPk());

            $cacheKey = $this->getCacheKey();

            if (!$this->getCache()->has($cacheKey, $r)) {
                $r = [];
                $r[] = $this->getRawItems();

                if ($this->query->foundRows) {
                    $r[] = $this->getRawFoundRows();
                }

                $this->getCache()->set($cacheKey, $r);
            }

            $pk = $this->entity->getPk();

            $this->items = $this->getByCacheFindMany(array_map(function ($item) use ($pk) {
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

    public function getItems()
    {
        return $this->exec()->items;
    }

    public function getFoundRows()
    {
        return $this->exec()->foundRows;
    }

    public function getTotal()
    {
        return $this->getFoundRows();
    }

    /**
     * @todo cache
     * @return int|array
     */
    public function getCount()
    {
        return $this->getDb()->selectCount($this->entity->getTable(), $this->query);
    }

    public function getArrays($idAsKeyOrKey = null): array
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

    public function getArray(): ?array
    {
        if (!$this->isReqCache()) {
            $this->setLimit(1);
        }

        return ($tmp = $this->getArrays()) ? $tmp[0] : null;
    }

    /**
     * @todo use DbInterface::reqToObjects()
     * @param null $idAsKeyOrKey
     * @return Entity[]
     */
    public function getObjects($idAsKeyOrKey = null): array
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

    public function getColumnToColumn(string $keyColumn, string $valueColumn): array
    {
        $output = [];

        foreach ($this->setColumns([$keyColumn, $valueColumn])->getItems() as $row) {
            $output[$row[$keyColumn]] = $row[$valueColumn];
        }

        return $output;
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
     * @return Entity
     */
    public function find($id)
    {
        $key = $this->getCacheKeyById($id);

        if ($this->getCache()->has($key, $output)) {
            return self::makeObjectFromCache($output);
        }

        $cache = $this->getDb()
            ->selectOne($this->entity->getTable(), new Query(['params' => [], 'where' => [
                $this->entity->getPk() => $this->entity->normalizeId($id),
            ]]));

        $this->getCache()->set($key, $cache);

        return self::makeObjectFromCache($cache);
    }

    /**
     * @todo add mixed types support (arrays or objects)
     * @param $id
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

        if ($this->getCache()->has($key, $output)) {
            return self::makeObjectFromCache($output);
        }

        $cache = $this->getDb()->selectOne($this->entity->getTable(), new Query(['params' => [], 'where' => [$column => $value]]));

        $this->getCache()->set($key, $cache);

        return self::makeObjectFromCache($cache);
    }

    protected function getByCacheFindMany(array $id)
    {
        /**
         * Universal smart function
         * Returns items from Cache or throw $valueGenerator, result as (id1 => val1, id2 => val2, ...)
         * @param array $keys
         * @param callable $keyGenerator - fn(ID) {return string}
         * @param callable $valueGenerator - fn(IDs) {return array(id1 => item1, ...)}
         * @return array
         */
        return (function (array $keys, callable $keyGenerator, callable $valueGenerator): array {

            $keys = array_unique($keys);
            $output = $k = $keys2fetch = [];

            foreach ($keys as $key) {
                $k[] = call_user_func($keyGenerator, $key);
            }

            $k2rawK = array_combine($k, $keys);
            $srcCache = $this->getCache()->getMulti($k);

            foreach ($k as $key) {
                if (array_key_exists($key, $srcCache)) {
                    $output[$k2rawK[$key]] = $srcCache[$key];
                } else {
                    $keys2fetch[] = $k2rawK[$key];
                }
            }

            if ($keys2fetch) {
                $key2v = $existing = [];
                $id2k = array_combine($keys, $k);

                foreach (call_user_func($valueGenerator, $keys2fetch) as $id => $item) {
                    $existing[] = $id;
                    $output[$id] = $item;
                    $key2v[$id2k[$id]] = $item;
                }

                foreach (array_diff($keys2fetch, $existing) as $id) {
                    $output[$id] = null;
                    $key2v[$id2k[$id]] = null;
                }

                $this->getCache()->setMulti($key2v);
                $ordered = [];

                foreach ($keys as $id) {
                    $ordered[$id] = $output[$id];
                }

                $output = $ordered;
            }

            return $output;
        })(
            $id,
            [$this, 'getCacheKeyById'],
            function (array $id) {
                $output = [];

                $id = array_map([$this->entity, 'normalizeId'], $id);
                $pk = $this->entity->getPk();

                $tmp = $this->getDb()
                    ->selectMany($this->entity->getTable(), new Query(['params' => [], 'where' => [$pk => $id]]));

                foreach ($tmp as $item) {
                    $output[$item[$pk]] = $item;
                }

                return $output;
            }
        );
    }

    /**
     * @param array $id
     * @return Entity[]
     */
    public function findMany(array $id)
    {
        return array_map(function ($cache) {
            return self::makeObjectFromCache($cache);
        }, $this->getByCacheFindMany(array_map([$this->entity, 'normalizeId'], $id)));
    }

    protected function getByCacheFindManyBy($column, array $value)
    {
        /**
         * Universal smart function
         * Returns items from Cache or throw $valueGenerator, result as (id1 => val1, id2 => val2, ...)
         * @param array $keys
         * @param callable $keyGenerator - fn(ID) {return string}
         * @param callable $valueGenerator - fn(IDs) {return array(id1 => item1, ...)}
         * @param array $args
         * @return array
         */
        return (function (array $keys, callable $keyGenerator, callable $valueGenerator, array $args): array {
            $keys = array_unique($keys);
            $output = $k = $keys2fetch = [];

            $tmp = $args;
            array_unshift($tmp, $keyGenerator);

            foreach ($keys as $key) {
                $tmp2 = $tmp;
                $tmp2[] = $key;
                $k[] = call_user_func(...$tmp2);
            }

            $k2rawK = array_combine($k, $keys);
            $srcCache = $this->getCache()->getMulti($k);

            foreach ($k as $key) {
                if (array_key_exists($key, $srcCache)) {
                    $output[$k2rawK[$key]] = $srcCache[$key];
                } else {
                    $keys2fetch[] = $k2rawK[$key];
                }
            }

            if ($keys2fetch) {
                $key2v = $existing = [];
                $id2k = array_combine($keys, $k);

                $tmp = $args;
                array_unshift($tmp, $valueGenerator);
                $tmp[] = $keys2fetch;

                foreach (call_user_func(...$tmp) as $id => $item) {
                    $existing[] = $id;
                    $output[$id] = $item;
                    $key2v[$id2k[$id]] = $item;
                }

                foreach (array_diff($keys2fetch, $existing) as $id) {
                    $output[$id] = null;
                    $key2v[$id2k[$id]] = null;
                }

                $this->getCache()->setMulti($key2v);
                $ordered = [];

                foreach ($keys as $id) {
                    $ordered[$id] = $output[$id];
                }

                $output = $ordered;
            }

            return $output;
        })(
            $value,
            [$this, 'getCacheKeyByColumnValue'],
            function ($column, array $value) {
                return $this->copy(true)
                    ->setWhere([$column => $value])
                    ->getArrays($column);
            },
            [$column]
        );
    }

    public function findManyBy($column, array $value)
    {
        if ($column == $this->entity->getPk()) {
            return $this->findMany($value);
        }

        return array_map(function ($cache) {
            return self::makeObjectFromCache($cache);
        }, $this->getByCacheFindManyBy($column, $value));
    }

    public function getCacheKeyByColumnValue($column, $value)
    {
        return $this->entity->getTable() . '-' . $column . '-' . $value;
    }

    public function selectBy($column, $value)
    {
        return $this->populate($this->getDb()->selectMany(
            $this->entity->getTable(),
            new Query(['params' => [], 'where' => [$column => $value]])
        ));
    }

    public function selectByWhere(array $where = [])
    {
        return $this->populate($this->getDb()->selectMany(
            $this->entity->getTable(),
            new Query(['params' => [], 'where' => $where])
        ));
    }

    /**
     * @param array $rows
     * @param \Closure|null $fn
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
     * @param array $row
     * @param \Closure|null $fn
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

    public function getIndexerIndex(): ?string
    {
        $table = $this->entity->getTable();
        return $table;
        $indexes = $this->getIndexer()->getAliasIndexes($table);

        if (!$indexes) {
            return null;
        }

        return $indexes[0];
    }

    public function getIndexerDocument(Entity $entity): ?array
    {
        return null;
    }

    public function addToIndex(Entity $entity, bool $update = false): ?bool
    {
        if (!$document = $this->getIndexerDocument($entity)) {
            return null;
        }

        if (!$index = $this->getIndexerIndex()) {
            return null;
        }

        if ($update) {
            return $this->getIndexer()->updateOne($index, $entity->getId(), $document);
        }

        return $this->getIndexer()->indexOne($index, $entity->getId(), $document);
    }

    public function deleteFromIndex(Entity $entity): ?bool
    {
        if (!$document = $this->getIndexerDocument($entity)) {
            return null;
        }

        if (!$index = $this->getIndexerIndex()) {
            return null;
        }

        return $this->getIndexer()->deleteOne($index, $entity->getId());
    }

    /**
     * @todo check required...
     * @param Entity $entity
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
     * @param array $params
     * @param Entity $entity
     * @return array|bool|int|mixed|null|DateTime|string
     */
    public function insertOne(Entity $entity, array $params = []): ?bool
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

        $aff = $this->app->container->db->insertOne(
            $entity->getTable(),
            $values,
            new Query(array_merge(
                $params,
                [
                    'params' => [],
                ]
            ))
        );

        if (0 < $aff) {
//            if (array_key_exists('int_id', $entity->getColumns())) {
//                $entity->set('int_id', $this->app->services->{$this->storage}->getReqInsertedId());
//            } else {
            if (!is_array($entity->getPk())) {
                $entity->setId($this->getDb()->insertedId());
            }
//            }

            $this->onInserted($entity);

            $entity->dropPrevAttrs();

            return true;
        }

        return false;
    }

    /**
     * @param Entity $entity
     * @param array $params
     * @return bool|null
     */
    public function replaceOne(Entity $entity, array $params = []): ?bool
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

        $aff = $this->app->container->db->replaceOne(
            $entity->getTable(),
            $values,
            new Query(array_merge(
                $params,
                [
                    'params' => [],
                ]
            ))
        );

        if (0 < $aff) {
//            if (array_key_exists('int_id', $entity->getColumns())) {
//                $entity->set('int_id', $this->app->services->{$this->storage}->getReqInsertedId());
//            } else {
            if (!is_array($entity->getPk())) {
                $entity->setId($this->getDb()->insertedId());
            }
//            }

            $this->onInserted($entity);

            $entity->dropPrevAttrs();

            return true;
        }

        return false;
    }

    public function insertMany(array $values, array $params = []): int
    {
        return $this->app->container->db->insertMany(
            $this->entity->getTable(),
            $values,
            new Query(array_merge(
                $params,
                [
                    'params' => [],
                ]
            ))
        );
    }

    /**
     * @todo check required...
     * @param \SNOWGIRL_CORE\Entity $entity
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
     * @param array $params
     * @param Entity $entity
     * @return bool|null
     */
    public function updateOne(Entity $entity, array $params = []): ?bool
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

        $aff = $this->app->container->db->updateMany(
            $entity->getTable(),
            $values,
            new Query(array_merge(
                $params,
                [
                    'params' => [],
                    'where' => $entity->getPkWhere(true),
                ]
            ))
        );

        if (0 < $aff) {
            $this->onUpdated($entity);

            return true;
        }

        return false;
    }

    /**
     * @todo add mixed types support (arrays or objects)
     * @param null $where
     * @param array $params
     * @param array $values
     * @return int
     */
    public function updateMany(array $values, $where = null, array $params = []): int
    {
        return $this->app->container->db->updateMany(
            $this->entity->getTable(),
            $values,
            new Query(array_merge(
                $params,
                [
                    'params' => [],
                    'where' => $where,
                ]
            ))
        );
    }

    public function save(Entity $entity, array $params = []): ?bool
    {
        return $entity->isNew() ? $this->insertOne($entity, $params) : $this->updateOne($entity, $params);
    }

    public function deleteCache(Entity $entity): bool
    {
        return $this->getCache()->delete($this->getCacheKeyByEntity($entity));
    }

    protected function onDelete(Entity $entity)
    {
        false && $entity;
        return true;
    }

    protected function onDeleted(Entity $entity)
    {
        $output = $this->deleteCache($entity);
//        $output = $output && $this->getCache()->delete($this->getAllIDsCacheKey());
        return $output;
    }

    /**
     * @todo add mixed types support (arrays or objects)
     * @param \SNOWGIRL_CORE\Entity $entity
     * @return bool
     */
    public function deleteOne(Entity $entity): ?bool
    {
        if (!$this->onDelete($entity)) {
            return null;
        }

        $req = new Query(['where' => $entity->getPkWhere()]);

        if ($this->getDb()->deleteOne($entity->getTable(), $req)) {
            $this->onDeleted($entity);

            return true;
        }

        return false;
    }

    /**
     * @todo add mixed types support (arrays or objects)
     * @param array $params
     * @param null $where
     * @return int
     */
    public function deleteMany($where = null, array $params = []): int
    {
        return $this->getDb()->deleteMany(
            $this->entity->getTable(),
            new Query(array_merge(
                $params,
                [
                    'params' => [],
                    'where' => $where,
                ]
            ))
        );
    }

    /**
     * @return Entity[]
     * @throws \Exception
     */
    public function findAll()
    {
        if (!$this->getCache() instanceof NullCache) {
            return $this->findMany($this->getAllIDs());
        }

        $k = $this->getAllIDsCacheKey();

        //get frontend cache
        if ($this->getCache()->has($k, $allIDs)) {
            return $this->findMany($allIDs);
        }

        $all = $this->copy(true)->getArrays(true);

        //set frontend cache
        $this->getCache()->set($k, array_keys($all));

        foreach ($all as $id => $item) {
            $this->getCache()->set($this->getCacheKeyById($id), $item);
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
        return $table . '-' . (int) $id;
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
     * @param null $key
     * @return array
     */
    public function getList($key = null)
    {
        return array_keys($this->addColumn($key ? $this->getDb()->makeDistinctExpression($key) : $this->entity->getPk())
            ->getArrays($key ?: true));
    }

    public function getIDs()
    {
        return $this->getList();
    }

    /**
     * @param array $id
     * @param array $keyToEntity
     * @param callable|null $fn
     * @return array|Entity[]
     */
    public function populateList(array $id, array $keyToEntity = [], callable $fn = null): array
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
     * @param null $name
     * @param bool|false $multiple
     * @param array $params
     * @param View|null $view
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
                'search_use_fulltext' => 1,
            ]),
        ], $params), $view);
    }

    /**
     * @param null $name
     * @param bool|false $multiple
     * @param View|null $view
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
            'required' => in_array(Entity::REQUIRED, $columns[$name]),
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
     * @param null $v
     * @return Manager
     */
    public function setLinked(Entity $entity, $k, $v = null)
    {
        $entity->setLinked($k, $v);
        return $this;
    }

    /**
     * @todo separate method for many-to-many entities...
     * @param                       $k
     * @param \SNOWGIRL_CORE\Entity $entity
     * @return null|Entity
     */
    public function getLinked(Entity $entity, $k)
    {
        $this->bindLinkedObjects([$entity], $k);

        return $entity->getLinked($k);
    }

    /**
     * @param Entity[] $entities
     * @param          $k
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
     * @param string $key
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
        $entity && $params && $domain;

        return null;
    }

    /**
     * @param bool|false $clear
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

    protected function getProviderClasses(): array
    {
        return [
            self::class,
        ];
    }

    protected function getProviderKeys(): array
    {
        return [
            $this->entity->getTable(),
            $this->entity->getTable() . 's',
        ];
    }

    protected function getDataProviderName(): string
    {
        if (null === $this->dataProviderName) {
            $provider = 'db';

            foreach ($this->getProviderKeys() as $providerKey) {
                if ($tmp = $this->app->config('data.provider.' . $providerKey)) {
                    $provider = $tmp;
                    break;
                }
            }

            $this->dataProviderName = $provider;
        }

        return $this->dataProviderName;
    }

    /**
     * @param string $provider
     * @return DataProvider
     * @throws Exception
     */
    protected function getRawDataProvider(string $provider): DataProvider
    {
        $class = null;

        foreach ($this->getProviderClasses() as $providerClass) {
            $tmp = $providerClass . '\\DataProvider\\' . ucfirst($provider) . 'DataProvider';

            if (class_exists($tmp)) {
                $class = $tmp;
                break;
            }
        }

        if (!$class) {
            throw new Exception('undefined provider class');
        }

        return new $class($this);
    }

    /**
     * @param string|null $forceProvider
     * @return DataProvider
     * @throws Exception
     */
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
     * @param string $query
     * @param bool $prefix
     * @param string|null $forceProvider
     * @return Entity[]
     * @throws Exception
     */
    public function getObjectsByQuery(string $query, bool $prefix = false, string $forceProvider = null): array
    {
        return $this->populateList($this->getDataProvider($forceProvider)->getListByQuery($query, $prefix));
    }

    /**
     * @param string $query
     * @param bool $prefix
     * @param string|null $forceProvider
     * @return int
     * @throws Exception
     */
    public function getCountByQuery(string $query, bool $prefix = false, string $forceProvider = null): int
    {
        return $this->getDataProvider($forceProvider)->getCountByQuery($query, $prefix);
    }

    /**
     * @param $query
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

        if (!$this->getCache()->has($k, $output)) {
            $output = $this->copy(true)->getIDs();

            $this->getCache()->set($k, $output);
        }

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

    public function setQueryParam($k, $v): Manager
    {
        $this->query->$k = $v;
        return $this;
    }
}