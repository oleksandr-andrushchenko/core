<?php

namespace SNOWGIRL_CORE\Indexer;

interface IndexerInterface
{
    public function search(string $index, array $boolFilter, int $offset = 0, int $limit = 10, array $sort = [], array $columns = []);

    public function count(string $index, array $boolFilter);

    public function countRaw(string $index, array $params);

    public function searchRaw(string $index, array $params, array $paths = []);

    public function searchIds(string $index, array $params): array;

    public function makeQueryString(string $query, bool $prefix = false): string;


    public function addBody(array &$params = []): IndexerInterface;

    public function addFrom(array &$params, int $from): IndexerInterface;

    public function addSize(array &$params, int $size): IndexerInterface;

    public function addQueryBoolFilter(array &$params, array $filter): IndexerInterface;

    public function addQueryQueryString(array &$params, string $query, $field): IndexerInterface;

    public function addAggsTerms(array &$params, string $field, int $size = 999999): IndexerInterface;

    public function addAggsNestedAggsTerms(array &$params, string $path, string $field, int $size = 999999): IndexerInterface;

    public function addAggsNestedBoolFilteredAggsTerms(array &$params, string $path, array $boolFilter, string $field, int $size = 999999): IndexerInterface;

    public function addNestedBoolFilterNode(array &$params, string $path, array $boolFilter): IndexerInterface;

    public function addSort(array &$params, $sort): IndexerInterface;


    public function getManager(): IndexerManagerInterface;
}