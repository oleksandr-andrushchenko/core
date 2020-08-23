<?php

namespace SNOWGIRL_CORE\Db\Decorator;

use SNOWGIRL_CORE\Db\DbInterface;
use SNOWGIRL_CORE\Db\DbManagerInterface;
use SNOWGIRL_CORE\Query;
use SNOWGIRL_CORE\Query\Expression;

class AbstractDbDecorator implements DbInterface
{
    /**
     * @var DbInterface
     */
    protected $db;

    public function __construct(DbInterface $db)
    {
        $this->db = $db;
    }

    public function getSchema(): ?string
    {
        return $this->db->getSchema();
    }

    public function insertOne(string $table, array $values, Query $query = null): bool
    {
        return $this->db->insertOne($table, $values, $query);
    }

    public function replaceOne(string $table, array $values, Query $query = null): bool
    {
        return $this->db->replaceOne($table, $values, $query);
    }

    public function insertMany(string $table, array $values, Query $query = null): int
    {
        return $this->db->insertMany($table, $values, $query);
    }

    public function selectOne(string $table, Query $query = null): ?array
    {
        return $this->db->selectOne($table, $query);
    }

    public function selectMany(string $table, Query $query = null): array
    {
        return $this->db->selectMany($table, $query);
    }

    public function selectCount(string $table, Query $query = null)
    {
        return $this->db->selectCount($table, $query);
    }

    public function selectFromEachGroup(string $table, $group, int $perGroup, Query $query = null): array
    {
        return $this->db->selectFromEachGroup($table, $group, $perGroup, $query);
    }

    public function updateOne(string $table, array $values, Query $query = null): bool
    {
        return $this->db->updateOne($table, $values, $query);
    }

    public function updateMany(string $table, array $values, Query $query = null): int
    {
        return $this->db->updateMany($table, $values, $query);
    }

    public function deleteOne(string $table, Query $query = null): bool
    {
        return $this->db->deleteOne($table, $query);
    }

    public function deleteMany(string $table, Query $query = null): int
    {
        return $this->db->deleteMany($table, $query);
    }

    public function deleteFromEachGroup(string $table, $group, int $perGroup, Query $query = null, $joinOn = null, $inverse = false): int
    {
        return $this->db->deleteFromEachGroup($table, $group, $perGroup, $query, $joinOn, $inverse);
    }


    public function isTransactionOpened(): ?bool
    {
        return $this->db->isTransactionOpened();
    }

    public function openTransaction(): bool
    {
        return $this->db->openTransaction();
    }

    public function commitTransaction(): bool
    {
        return $this->db->commitTransaction();
    }

    public function rollBackTransaction(): bool
    {
        return $this->db->rollBackTransaction();
    }

    public function makeTransaction(callable $fnOk, $default = null)
    {
        return $this->db->makeTransaction($fnOk, $default);
    }


    public function req($query): DbInterface
    {
        return $this->db->req($query);
    }

    public function reqToArrays($query, $key = null): array
    {
        return $this->db->reqToArrays($query, $key);
    }

    public function reqToArray($query): array
    {
        return $this->db->reqToArray($query);
    }

    public function reqToObjects($query, $className, $key = null): array
    {
        return $this->db->reqToObjects($query, $className, $key);
    }


    public function makeSelectSQL($columns = '*', $isFoundRows = false, array &$params = [], $table = null): string
    {
        return $this->db->makeSelectSQL($columns, $isFoundRows, $params, $table);
    }

    public function makeDistinctExpression($column, $table = null): Expression
    {
        return $this->db->makeDistinctExpression($column, $table);
    }

    public function makeFromSQL($tables): string
    {
        return $this->db->makeFromSQL($tables);
    }

    public function makeJoinSQL($join, array &$params): string
    {
        return $this->db->makeJoinSQL($join, $params);
    }

    public function makeIndexSQL($index): string
    {
        return $this->db->makeIndexSQL($index);
    }

    public function makeWhereSQL($where, array &$params, $table = null, $placeholders = false): string
    {
        return $this->db->makeWhereSQL($where, $params, $table, $placeholders);
    }

    public function makeGroupSQL($group, array &$params, $table = null): string
    {
        return $this->db->makeGroupSQL($group, $params, $table);
    }

    public function makeOrderSQL($order, array &$params, $table = null): string
    {
        return $this->db->makeOrderSQL($order, $params, $table);
    }

    public function makeLimitSQL($offset, $limit, array &$params): string
    {
        return $this->db->makeLimitSQL($offset, $limit, $params);
    }

    public function makeWhereClauseSQL($where, array &$params, $table = null, $placeholders = false): string
    {
        return $this->db->makeWhereClauseSQL($where, $params, $table, $placeholders);
    }

    public function makeHavingSQL($where, array &$params, $table = null, $placeholders = false): string
    {
        return $this->db->makeHavingSQL($where, $params, $table, $placeholders);
    }

    public function makeQuery($rawQuery, bool $isLikeInsteadMatch = false): string
    {
        return $this->db->makeQuery($rawQuery, $isLikeInsteadMatch);
    }

    public function quote($v, $table = null): string
    {
        return $this->db->quote($v, $table);
    }


    public function foundRows(): int
    {
        return $this->db->foundRows();
    }

    public function affectedRows(): int
    {
        return $this->db->affectedRows();
    }

    public function numRows(): int
    {
        return $this->db->numRows();
    }

    public function insertedId()
    {
        return $this->db->insertedId();
    }


    public function getManager(): DbManagerInterface
    {
        return $this->db->getManager();
    }
}