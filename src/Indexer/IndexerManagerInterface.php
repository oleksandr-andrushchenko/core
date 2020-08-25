<?php

namespace SNOWGIRL_CORE\Indexer;

interface IndexerManagerInterface
{
    public function createIndex(string $name, array $mappings = []): bool;

    public function deleteIndex(string $name): bool;

    public function indexMany(string $name, array $documents): int;

    public function switchAliasIndex(string $alias, array $mappings = [], callable $job = null);
}