<?php

namespace SNOWGIRL_CORE\Db;

interface DbManagerInterface
{
    public function getTables(): array;

    public function getColumns(string $table): array;

    public function showCreateTable(string $table, bool $ifNotExists = true, bool $autoIncrement = false);

    public function createTable(string $table, array $records, string $engine = 'InnoDB', string $charset = 'utf8', bool $temporary = false): bool;

    public function createLikeTable(string $table, string $newTable): bool;

    public function addTableColumn(string $table, string $column, $options): bool;

    public function dropTableColumn(string $table, string $column): bool;

    public function renameTable(string $table, string $newTable): bool;

    public function truncateTable(string $table): bool;

    public function dropTable(string $table): bool;

    public function getIndexes(string $table): array;

    public function addTableKey(string $table, string $key, $column, $unique = false): bool;

    public function dropTableKey(string $table, string $key, bool $unique = false): bool;
}