<?php

namespace SNOWGIRL_CORE\Indexer;

class NullIndexer implements IndexerInterface
{
    public function search(string $index, array $boolFilter, int $offset = 0, int $limit = 10, array $sort = [], array $columns = [])
    {
        return [];
    }

    public function count(string $index, array $boolFilter)
    {
        return 0;
    }

    public function countRaw(string $index, array $params)
    {
        return 0;
    }

    public function searchRaw(string $index, array $params, array $paths = [])
    {
        return [];
    }

    public function searchIds(string $index, array $params): array
    {
        return [];
    }

    public function makeQueryString(string $query, bool $prefix = false): string
    {
        return '';
    }


    public function addBody(array &$params = []): IndexerInterface
    {
        $params['body'] = [];

        return $this;
    }

    public function addFrom(array &$params, int $from): IndexerInterface
    {
        return $this;
    }

    public function addSize(array &$params, int $size): IndexerInterface
    {
        return $this;
    }

    public function addQueryBoolFilter(array &$params, array $filter): IndexerInterface
    {
        return $this;
    }

    public function addQueryQueryString(array &$params, string $query, $field): IndexerInterface
    {
        return $this;
    }

    public function addAggsTerms(array &$params, string $field, int $size = 999999): IndexerInterface
    {
        return $this;
    }

    public function addAggsNestedAggsTerms(array &$params, string $path, string $field, int $size = 999999): IndexerInterface
    {
        return $this;
    }

    public function addAggsNestedBoolFilteredAggsTerms(array &$params, string $path, array $boolFilter, string $field, int $size = 999999): IndexerInterface
    {
        return $this;
    }

    public function addNestedBoolFilterNode(array &$params, string $path, array $boolFilter): IndexerInterface
    {
        return $this;
    }

    public function addSort(array &$params, $sort): IndexerInterface
    {
        return $this;
    }


    public function indexOne(string $index, $id, array $document): bool
    {
        return false;
    }

    public function updateOne(string $index, $id, array $document): bool
    {
        return false;
    }

    public function deleteOne(string $index, $id): bool
    {
        return false;
    }

    public function getAliasIndexes(string $alias, bool $withAliasOnly = false): array
    {
        return [];
    }


    public function getManager(): IndexerManagerInterface
    {
        // TODO: Implement getManager() method.
    }
}