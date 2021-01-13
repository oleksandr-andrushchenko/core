<?php

namespace SNOWGIRL_CORE\Elasticsearch;

abstract class ElasticsearchAbstractDecorator implements ElasticsearchInterface
{
    /**
     * @var Elasticsearch
     */
    private $elasticsearch;

    public function __construct(Elasticsearch $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    public function search(string $index, ElasticsearchQuery $query): array
    {
        return $this->elasticsearch->search($index, $query);
    }

    public function searchByParams(string $index, array $params, ElasticsearchQuery $query = null): array
    {
        return $this->elasticsearch->searchByParams($index, $params, $query);
    }

    public function count(string $index, ElasticsearchQuery $query): int
    {
        return $this->elasticsearch->count($index, $query);
    }

    public function countByParams(string $index, array $params, ElasticsearchQuery $query = null): int
    {
        return $this->elasticsearch->countByParams($index, $params, $query);
    }

    public function indexOne(string $index, $id, array $document): bool
    {
        return $this->elasticsearch->indexOne($index, $index, $document);
    }

    public function updateOne(string $index, $id, array $document): bool
    {
        return $this->elasticsearch->updateOne($index, $id, $document);
    }

    public function deleteOne(string $index, $id): bool
    {
        return $this->elasticsearch->deleteOne($index, $id);
    }

    public function indexMany(string $name, array $documents): int
    {
        return $this->elasticsearch->indexMany($name, $documents);
    }

    public function deleteManyByDocumentIds(string $index, array $ids): int
    {
        return $this->elasticsearch->deleteManyByDocumentIds($index, $ids);
    }

    public function deleteByParams(string $index, array $params, ElasticsearchQuery $query = null): int
    {
        return $this->elasticsearch->deleteByParams($index, $params, $query);
    }

    public function deleteIndex(string $name): bool
    {
        return $this->elasticsearch->deleteIndex($name);
    }

    public function switchAliasIndex(string $alias, array $mappings = [], callable $job = null)
    {
        return $this->elasticsearch->switchAliasIndex($alias, $mappings, $job);
    }

    public function createIndex(string $name, array $mappings = []): bool
    {
        return $this->elasticsearch->createIndex($name, $mappings);
    }
}