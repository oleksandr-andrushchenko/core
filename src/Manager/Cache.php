<?php

namespace SNOWGIRL_CORE\Manager;

use SNOWGIRL_CORE\Manager;
use SNOWGIRL_CORE\Entity\Cache as CacheEntity;
use SNOWGIRL_CORE\Query;

class Cache extends Manager
{
    protected $masterServices = true;

    public function set(string $k, $v)
    {
        $item = new CacheEntity([
            'key' => $k,
            'value' => serialize($v),
        ]);

        return $this->replaceOne($item);
    }

    public function get(string $k)
    {
        /** @var CacheEntity $entity */

        if (!$entity = $this->findBy('key', $k)) {
            return null;
        }

        return unserialize($entity->getValue());
    }

    public function delete(string $k)
    {
        return $this->deleteBy('key', $k);
    }

    protected function deleteBy($column, $value)
    {
        $query = new Query(['params' => []]);
        $query->where = $this->getDb()->makeWhereSQL([$column => $value], $query->params, null, $query->placeholders);

        return $this->getDb()->deleteOne($this->getEntity()->getTable(), $query);
    }
}