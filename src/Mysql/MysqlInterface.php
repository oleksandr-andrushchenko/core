<?php

namespace SNOWGIRL_CORE\Mysql;

interface MysqlInterface
{
    public function getSchema(): ?string;

    public function insertOne(string $table, array $values, MysqlQuery $query = null): bool;

    public function replaceOne(string $table, array $values, MysqlQuery $query = null): bool;

    public function insertMany(string $table, array $values, MysqlQuery $query = null): int;

    public function selectOne(string $table, MysqlQuery $query = null): ?array;

    public function selectMany(string $table, MysqlQuery $query = null): array;

    public function selectCount(string $table, MysqlQuery $query = null);

    public function selectFromEachGroup(string $table, $group, int $perGroup, MysqlQuery $query = null): array;

    public function updateOne(string $table, array $values, MysqlQuery $query = null): bool;

    public function updateMany(string $table, array $values, MysqlQuery $query = null): int;

    public function deleteOne(string $table, MysqlQuery $query = null): bool;

    public function deleteMany(string $table, MysqlQuery $query = null): int;

    public function deleteFromEachGroup(string $table, $group, int $perGroup, MysqlQuery $query = null, $joinOn = null, $inverse = false): int;

    public function isTransactionOpened(): ?bool;

    public function openTransaction(): bool;

    public function commitTransaction(): bool;

    public function rollBackTransaction(): bool;

    public function makeTransaction(callable $fnOk, $default = null);

    public function req($query): MysqlInterface;

    public function reqToArrays($query, $key = null): array;

    public function reqToArray($query): array;

    public function reqToObjects($query, $className, $key = null): array;

    public function makeSelectSQL($columns = '*', $isFoundRows = false, array &$params = [], $table = null): string;

    public function makeDistinctExpression($column, $table = null): MysqlQueryExpression;

    public function makeFromSQL($tables): string;

    public function makeJoinSQL($join, array &$params): string;

    public function makeIndexSQL($index): string;

    public function makeWhereSQL($where, array &$params, $table = null, $placeholders = false): string;

    public function makeGroupSQL($group, array &$params, $table = null): string;

    public function makeOrderSQL($order, array &$params, $table = null): string;

    public function makeLimitSQL($offset, $limit, array &$params): string;

    public function makeWhereClauseSQL($where, array &$params, $table = null, $placeholders = false): string;

    public function makeHavingSQL($where, array &$params, $table = null, $placeholders = false): string;

    public function makeQuery($rawQuery, bool $isLikeInsteadMatch = false): string;

    public function quote($v, $table = null): string;

    public function foundRows(): int;

    public function affectedRows(): int;

    public function numRows(): int;

    public function insertedId();

    public function getTables(): array;

    public function getColumns(string $table): array;

    public function showCreateTable(string $table, bool $ifNotExists = false, bool $autoIncrement = false): string;

    public function showCreateColumn(string $table, string $column): string;

    public function columnExists(string $table, string $column): bool;

    public function indexExists(string $table, $column, string $key = null, bool $unique = false): bool;

    public function createTable(string $table, array $records, string $engine = 'InnoDB', string $charset = 'utf8', bool $temporary = false): bool;

    public function createLikeTable(string $table, string $newTable): bool;

    public function addTableColumn(string $table, string $column, $options): bool;

    public function dropTableColumn(string $table, string $column): bool;

    public function renameTable(string $table, string $newTable): bool;

    public function truncateTable(string $table): bool;

    public function dropTable(string $table): bool;

    public function getIndexes(string $table): array;

    public function addTableKey(string $table, $column, string $key = null, bool $unique = false): bool;

    public function dropTableKey(string $table, string $key, bool $unique = false): bool;
}