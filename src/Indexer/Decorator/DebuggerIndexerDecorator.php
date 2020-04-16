<?php

namespace SNOWGIRL_CORE\Indexer\Decorator;

use Psr\Log\LoggerInterface;
use SNOWGIRL_CORE\DebuggerTrait;
use SNOWGIRL_CORE\Indexer\IndexerInterface;

class DebuggerIndexerDecorator extends AbstractIndexerDecorator
{
    use DebuggerTrait;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(IndexerInterface $indexer, LoggerInterface $logger)
    {
        parent::__construct($indexer);

        $this->logger = $logger;
    }

    public function search(string $index, array $boolFilter, int $offset = 0, int $limit = 10, array $sort = [], array $columns = [])
    {
        return $this->debug(__FUNCTION__, [$index, $boolFilter, $offset, $limit, $sort, $columns]);
    }

    public function count(string $index, array $boolFilter)
    {
        return $this->debug(__FUNCTION__, func_get_args());
    }

    public function countRaw(string $index, array $params)
    {
        return $this->debug(__FUNCTION__, func_get_args());
    }

    public function searchRaw(string $index, array $params, array $paths = [])
    {
        return $this->debug(__FUNCTION__, [$index, $params, $paths]);
    }

    public function searchIds(string $index, array $params): array
    {
        return $this->debug(__FUNCTION__, func_get_args());
    }

    public function makeQueryString(string $query, bool $prefix = false): string
    {
        return $this->debug(__FUNCTION__, [$query, $prefix]);
    }

    public function insertOne(string $index, $id, array $document)
    {
        return $this->debug(__FUNCTION__, func_get_args());
    }
}