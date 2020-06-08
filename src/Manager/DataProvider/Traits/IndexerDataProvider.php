<?php

namespace SNOWGIRL_CORE\Manager\DataProvider\Traits;

use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\Manager;

/**
 * Trait Indexer
 *
 * @property Manager manager
 * @package SNOWGIRL_CORE\Manager\DataProvider\Traits
 */
trait IndexerDataProvider
{
    /**
     * @todo improve with
     *       https://www.elastic.co/guide/en/elasticsearch/guide/current/_index_time_search_as_you_type.html
     *
     * @param string $query
     * @param bool $prefix
     *
     * @return array
     */
    public function getListByQuery(string $query, bool $prefix = false): array
    {
        $db = $this->manager->getApp()->container->indexer($this->manager->getMasterServices());

        $params = [];

        $db->addBody($params)
            ->addFrom($params['body'], ($offset = $this->manager->getQuery()->offset) ? (int)$offset : 0)
            ->addSize($params['body'], ($limit = $this->manager->getQuery()->limit) ? (int)$limit : 999999);

        if ($query && $query = $db->makeQueryString($query, $prefix)) {
            $db->addQueryQueryString($params['body'], $query, $this->getSearchColumns());
        }

        return $db->addSort($params, $this->getListByQuerySort())
            ->searchIds($this->manager->getEntity()->getTable(), $params);
    }

    public function getCountByQuery(string $query, bool $prefix = false): int
    {
        $db = $this->manager->getApp()->container->indexer($this->manager->getMasterServices());

        $params = [];

        $db->addBody($params);

        if ($query && $query = $db->makeQueryString($query, $prefix)) {
            $db->addQueryQueryString($params['body'], $query, $this->getSearchColumns());
        }

        return $db->countRaw($this->manager->getEntity()->getTable(), $params);
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