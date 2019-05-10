<?php

namespace SNOWGIRL_CORE\Manager\DataProvider\Traits;

use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\Manager;

/**
 * Trait Elastic
 *
 * @property Manager manager
 * @package SNOWGIRL_CORE\Manager\DataProvider\Traits
 */
trait Elastic
{
    /**
     * @todo improve with
     *       https://www.elastic.co/guide/en/elasticsearch/guide/current/_index_time_search_as_you_type.html
     *
     * @param string $query
     * @param bool   $prefix
     *
     * @return array
     */
    public function getListByQuery(string $query, bool $prefix = false): array
    {
        $db = $this->manager->getApp()->storage->elastic(null, $this->manager->getMasterServices());

        $entity = $this->manager->getEntity();

        $params = [];

        return $db->addBody($params)
            ->addSize($params['body'], ($limit = $this->manager->getQuery()->limit) ? (int)$limit : 999999)
            ->addQueryQueryString($params['body'], $db->makeQueryString($query, $prefix), $this->getSearchColumns())
            ->addSort($params, $this->getListByQuerySort())
            ->searchIds($entity->getTable(), $params);
    }

    protected function getSearchColumns(): array
    {
        return $this->manager->findColumns(Entity::SEARCH_IN);
    }

    protected function getListByQuerySort(): array
    {
        return array_map(function ($column) {
            return $column . '_length:asc';
        }, $this->getSearchColumns());
    }
}