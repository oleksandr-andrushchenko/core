<?php

namespace SNOWGIRL_CORE\Elasticsearch;

interface ElasticsearchInterface
{
    public function search(string $index, ElasticsearchQuery $query): array;

    public function searchByParams(string $index, array $params, ElasticsearchQuery $query = null): array;

    public function count(string $index, ElasticsearchQuery $query): int;

    public function countByParams(string $index, array $params, ElasticsearchQuery $query = null): int;

    public function indexOne(string $index, $id, array $document): bool;

    public function updateOne(string $index, $id, array $document): bool;

    public function deleteOne(string $index, $id): bool;

    public function indexMany(string $name, array $documents): int;

    public function deleteManyByDocumentIds(string $index, array $ids): int;

    public function deleteByParams(string $index, array $params, ElasticsearchQuery $query = null): int;

    public function deleteIndex(string $name): bool;

    public function switchAliasIndex(string $alias, array $mappings = [], callable $job = null);

    public function createIndex(string $name, array $mappings = []): bool;
}
