<?php

namespace SNOWGIRL_CORE\Indexer\Decorator;

use SNOWGIRL_CORE\Indexer\IndexerInterface;
use SNOWGIRL_CORE\Indexer\IndexerManagerInterface;

class AbstractIndexerDecorator implements IndexerInterface
{
    /**
     * @var IndexerInterface
     */
    private $indexer;

    public function __construct(IndexerInterface $indexer)
    {
        $this->indexer = $indexer;
    }

    public function search(string $index, array $boolFilter, int $offset = 0, int $limit = 10, array $sort = [], array $columns = [])
    {
        return $this->indexer->search($index, $boolFilter, $offset, $limit, $sort, $columns);
    }

    public function count(string $index, array $boolFilter)
    {
        return $this->indexer->count($index, $boolFilter);
    }

    public function countRaw(string $index, array $params)
    {
        return $this->indexer->countRaw($index, $params);
    }

    public function searchRaw(string $index, array $params, array $paths = [])
    {
        return $this->indexer->searchRaw($index, $params, $paths);
    }

    public function searchIds(string $index, array $params): array
    {
        return $this->indexer->searchIds($index, $params);
    }

    public function makeQueryString(string $query, bool $prefix = false): string
    {
        return $this->indexer->makeQueryString($query, $prefix);
    }


    public function addBody(array &$params = []): IndexerInterface
    {
        return $this->indexer->addBody($params);
    }

    public function addFrom(array &$params, int $from): IndexerInterface
    {
        return $this->indexer->addFrom($params, $from);
    }

    public function addSize(array &$params, int $size): IndexerInterface
    {
        return $this->indexer->addSize($params, $size);
    }

    public function addQueryBoolFilter(array &$params, array $filter): IndexerInterface
    {
        return $this->indexer->addQueryBoolFilter($params, $filter);
    }

    public function addQueryQueryString(array &$params, string $query, $field): IndexerInterface
    {
        return $this->indexer->addQueryQueryString($params, $query, $field);
    }

    public function addAggsTerms(array &$params, string $field, int $size = 999999): IndexerInterface
    {
        return $this->indexer->addAggsTerms($params, $field, $size);
    }

    public function addAggsNestedAggsTerms(array &$params, string $path, string $field, int $size = 999999): IndexerInterface
    {
        return $this->indexer->addAggsNestedAggsTerms($params, $path, $field, $size);
    }

    public function addAggsNestedBoolFilteredAggsTerms(array &$params, string $path, array $boolFilter, string $field, int $size = 999999): IndexerInterface
    {
        return $this->indexer->addAggsNestedBoolFilteredAggsTerms($params, $path, $boolFilter, $field, $size);
    }

    public function addNestedBoolFilterNode(array &$params, string $path, array $boolFilter): IndexerInterface
    {
        return $this->indexer->addNestedBoolFilterNode($params, $path, $boolFilter);
    }

    public function addSort(array &$params, $sort): IndexerInterface
    {
        return $this->indexer->addSort($params, $sort);
    }


    public function getManager(): IndexerManagerInterface
    {
        return $this->indexer->getManager();
    }
}