<?php

namespace SNOWGIRL_CORE\Manager\DataProvider\Traits;

use SNOWGIRL_CORE\Elasticsearch\ElasticsearchQuery;
use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\Manager;

/**
 * @property Manager manager
 */
trait ElasticsearchDataProvider
{
    /**
     * @todo improve with
     *       https://www.elastic.co/guide/en/elasticsearch/guide/current/_index_time_search_as_you_type.html
     * @param string $term
     * @param bool $prefix
     * @return array
     */
    public function getListByQuery(string $term, bool $prefix = false): ?array
    {
        $index = $this->manager->getEntity()->getTable();
        $query = new ElasticsearchQuery();

        $query->queryStringFields = $this->getListByQuerySearchColumns();
        $query->queryStringQuery = $term;
        $query->queryStringPrefix = $prefix;
        $query->idsOnly = true;
        $query->sort = $this->getListByQuerySort();
        $query->from = $this->manager->getQuery()->offset;
        $query->size = $this->manager->getQuery()->limit;

        return $this->manager->getApp()->container
            ->elasticsearch($this->manager->getMasterServices())
            ->search($index, $query);
    }

    public function getCountByQuery(string $term, bool $prefix = false): ?int
    {
        $index = $this->manager->getEntity()->getTable();
        $query = new ElasticsearchQuery();

        $query->queryStringFields = $this->getListByQuerySearchColumns();
        $query->queryStringQuery = $term;
        $query->queryStringPrefix = $prefix;

        return $this->manager->getApp()->container
            ->elasticsearch($this->manager->getMasterServices())
            ->count($index, $query);
    }

    protected function getListByQuerySearchColumns(): array
    {
        return $this->manager->findColumns(Entity::SEARCH_IN);
    }

    protected function getListByQuerySort(): array
    {
        return array_map(function ($column) {
            return $column . '_length';
        }, $this->getListByQuerySearchColumns());
    }
}