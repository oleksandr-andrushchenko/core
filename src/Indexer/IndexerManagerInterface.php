<?php

namespace SNOWGIRL_CORE\Indexer;

interface IndexerManagerInterface
{
    public function createIndex(string $name, array $mappings = []): bool;

    public function deleteIndex(string $name): bool;

    public function indexMany(string $index, array $documents): int;

    public function switchAliasIndex(string $alias, string $index = null, callable $work = null);
}