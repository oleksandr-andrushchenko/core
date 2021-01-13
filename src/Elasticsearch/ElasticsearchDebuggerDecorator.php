<?php

namespace SNOWGIRL_CORE\Elasticsearch;

use Psr\Log\LoggerInterface;
use SNOWGIRL_CORE\DebuggerTrait;

class ElasticsearchDebuggerDecorator extends ElasticsearchAbstractDecorator
{
    use DebuggerTrait;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Elasticsearch $elasticsearch, LoggerInterface $logger)
    {
        parent::__construct($elasticsearch);

        $this->logger = $logger;
    }

    public function search(string $index, ElasticsearchQuery $query): array
    {
        return $this->debug(__FUNCTION__, [$index, $query]);
    }

    public function count(string $index, ElasticsearchQuery $query): int
    {
        return $this->debug(__FUNCTION__, [$index, $query]);
    }

    public function countByParams(string $index, array $params, ElasticsearchQuery $query = null): int
    {
        return $this->debug(__FUNCTION__, [$index, $params, $query]);
    }

    public function searchByParams(string $index, array $params, ElasticsearchQuery $query = null): array
    {
        return $this->debug(__FUNCTION__, [$index, $params, $query]);
    }

    public function insertOne(string $index, $id, array $document)
    {
        return $this->debug(__FUNCTION__, func_get_args());
    }

    public function updateOne(string $index, $id, array $document): bool
    {
        return $this->debug(__FUNCTION__, func_get_args());
    }

    public function deleteOne(string $index, $id): bool
    {
        return $this->debug(__FUNCTION__, func_get_args());
    }
}