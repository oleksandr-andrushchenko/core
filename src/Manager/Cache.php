<?php

namespace SNOWGIRL_CORE\Manager;

use SNOWGIRL_CORE\Manager;
use SNOWGIRL_CORE\Entity\Cache as CacheEntity;
use SNOWGIRL_CORE\Service\Storage\Query;

class Cache extends Manager
{
    protected $masterServices = true;

    public function set(string $k, $v)
    {
        $item = new CacheEntity([
            'key' => $k,
            'value' => serialize($v)
        ]);

        return $this->insertOne($item, true);
    }

    public function get(string $k)
    {
        /** @var CacheEntity $entity */

        if (!$entity = $this->findBy('key', $k)) {
            return null;
        }

        $v = unserialize($entity->getValue());

        return $v;
    }

    public function delete(string $k)
    {
        return $this->deleteBy('key', $k);
    }

    protected function deleteBy($column, $value)
    {
        $query = new Query(['params' => []]);
        $query->where = $this->getStorage()->makeWhereSQL([$column => $value], $query->params);

        return $this->getStorage()->deleteOne($this->getEntity()->getTable(), $query);
    }
}