<?php

namespace SNOWGIRL_CORE\Mysql;

abstract class MysqlAbstractDecorator implements MysqlInterface
{
    /**
     * @var Mysql
     */
    private $mysql;

    public function __construct(Mysql $mysql)
    {
        $this->mysql = $mysql;
    }

    public function getSchema(): ?string
    {
        return $this->mysql->getSchema();
    }

    public function insertOne(string $table, array $values, MysqlQuery $query = null): bool
    {
        return $this->mysql->insertOne($table, $values, $query);
    }

    public function replaceOne(string $table, array $values, MysqlQuery $query = null): bool
    {
        return $this->mysql->replaceOne($table, $values, $query);
    }

    public function insertMany(string $table, array $values, MysqlQuery $query = null): int
    {
        return $this->mysql->insertMany($table, $values, $query);
    }

    public function selectOne(string $table, MysqlQuery $query = null): ?array
    {
        return $this->mysql->selectOne($table, $query);
    }

    public function selectMany(string $table, MysqlQuery $query = null): array
    {
        return $this->mysql->selectMany($table, $query);
    }

    public function selectCount(string $table, MysqlQuery $query = null)
    {
        return $this->mysql->selectCount($table, $query);
    }

    public function selectFromEachGroup(string $table, $group, int $perGroup, MysqlQuery $query = null): array
    {
        return $this->mysql->selectFromEachGroup($table, $group, $perGroup, $query);
    }

    public function updateOne(string $table, array $values, MysqlQuery $query = null): bool
    {
        return $this->mysql->updateOne($table, $values, $query);
    }

    public function updateMany(string $table, array $values, MysqlQuery $query = null): int
    {
        return $this->mysql->updateMany($table, $values, $query);
    }

    public function deleteOne(string $table, MysqlQuery $query = null): bool
    {
        return $this->mysql->deleteOne($table, $query);
    }

    public function deleteMany(string $table, MysqlQuery $query = null): int
    {
        return $this->mysql->deleteMany($table, $query);
    }

    public function deleteFromEachGroup(string $table, $group, int $perGroup, MysqlQuery $query = null, $joinOn = null, $inverse = false): int
    {
        return $this->mysql->deleteFromEachGroup($table, $group, $perGroup, $query, $joinOn, $inverse);
    }

    public function isTransactionOpened(): ?bool
    {
        return $this->mysql->isTransactionOpened();
    }

    public function openTransaction(): bool
    {
        return $this->mysql->openTransaction();
    }

    public function commitTransaction(): bool
    {
        return $this->mysql->commitTransaction();
    }

    public function rollBackTransaction(): bool
    {
        return $this->mysql->rollBackTransaction();
    }

    public function makeTransaction(callable $fnOk, $default = null)
    {
        return $this->mysql->makeTransaction($fnOk, $default);
    }

    public function req($query): MysqlInterface
    {
        return $this->mysql->req($query);
    }

    public function reqToArrays($query, $key = null): array
    {
        return $this->mysql->reqToArrays($query, $key);
    }

    public function reqToArray($query): array
    {
        return $this->mysql->reqToArray($query);
    }

    public function reqToObjects($query, $className, $key = null): array
    {
        return $this->mysql->reqToObjects($query, $className, $key);
    }

    public function makeSelectSQL($columns = '*', $isFoundRows = false, array &$params = [], $table = null): string
    {
        return $this->mysql->makeSelectSQL($columns, $isFoundRows, $params, $table);
    }

    public function makeDistinctExpression($column, $table = null): MysqlQueryExpression
    {
        return $this->mysql->makeDistinctExpression($column, $table);
    }

    public function makeFromSQL($tables): string
    {
        return $this->mysql->makeFromSQL($tables);
    }

    public function makeJoinSQL($join, array &$params): string
    {
        return $this->mysql->makeJoinSQL($join, $params);
    }

    public function makeIndexSQL($index): string
    {
        return $this->mysql->makeIndexSQL($index);
    }

    public function makeWhereSQL($where, array &$params, $table = null, $placeholders = false): string
    {
        return $this->mysql->makeWhereSQL($where, $params, $table, $placeholders);
    }

    public function makeGroupSQL($group, array &$params, $table = null): string
    {
        return $this->mysql->makeGroupSQL($group, $params, $table);
    }

    public function makeOrderSQL($order, array &$params, $table = null): string
    {
        return $this->mysql->makeOrderSQL($order, $params, $table);
    }

    public function makeLimitSQL($offset, $limit, array &$params): string
    {
        return $this->mysql->makeLimitSQL($offset, $limit, $params);
    }

    public function makeWhereClauseSQL($where, array &$params, $table = null, $placeholders = false): string
    {
        return $this->mysql->makeWhereClauseSQL($where, $params, $table, $placeholders);
    }

    public function makeHavingSQL($where, array &$params, $table = null, $placeholders = false): string
    {
        return $this->mysql->makeHavingSQL($where, $params, $table, $placeholders);
    }

    public function makeQuery($rawQuery, bool $isLikeInsteadMatch = false): string
    {
        return $this->mysql->makeQuery($rawQuery, $isLikeInsteadMatch);
    }

    public function quote($v, $table = null): string
    {
        return $this->mysql->quote($v, $table);
    }

    public function foundRows(): int
    {
        return $this->mysql->foundRows();
    }

    public function affectedRows(): int
    {
        return $this->mysql->affectedRows();
    }

    public function numRows(): int
    {
        return $this->mysql->numRows();
    }

    public function insertedId()
    {
        return $this->mysql->insertedId();
    }

    public function indexExists(string $table, $column, string $key = null, bool $unique = false): bool
    {
        return $this->mysql->indexExists($table, $column, $key, $unique);
    }

    public function getIndexes(string $table): array
    {
        return $this->mysql->getIndexes($table);
    }

    public function dropTableColumn(string $table, string $column): bool
    {
        return $this->mysql->dropTableColumn($table, $column);
    }

    public function getColumns(string $table): array
    {
        return $this->mysql->getColumns($table);
    }

    public function showCreateColumn(string $table, string $column): string
    {
        return $this->mysql->showCreateColumn($table, $column);
    }

    public function addTableKey(string $table, $column, string $key = null, bool $unique = false): bool
    {
        return $this->mysql->addTableKey($table, $column, $key, $unique);
    }

    public function addTableColumn(string $table, string $column, $options): bool
    {
        return $this->mysql->addTableColumn($table, $column, $options);
    }

    public function columnExists(string $table, string $column): bool
    {
        return $this->mysql->columnExists($table, $column);
    }

    public function createLikeTable(string $table, string $newTable): bool
    {
        return $this->mysql->createLikeTable($table, $newTable);
    }

    public function createTable(string $table, array $records, string $engine = 'InnoDB', string $charset = 'utf8', bool $temporary = false): bool
    {
        return $this->mysql->createTable($table, $records, $engine, $charset, $temporary);
    }

    public function dropTable(string $table): bool
    {
        return $this->mysql->dropTable($table);
    }

    public function dropTableKey(string $table, string $key, bool $unique = false): bool
    {
        return $this->mysql->dropTableKey($table, $key, $unique);
    }

    public function getTables(): array
    {
        return $this->mysql->getTables();
    }

    public function renameTable(string $table, string $newTable): bool
    {
        return $this->mysql->renameTable($table, $newTable);
    }

    public function showCreateTable(string $table, bool $ifNotExists = false, bool $autoIncrement = false): string
    {
        return $this->mysql->showCreateTable($table, $ifNotExists, $autoIncrement);
    }

    public function truncateTable(string $table): bool
    {
        return $this->mysql->truncateTable($table);
    }
}