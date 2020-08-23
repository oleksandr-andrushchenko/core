<?php

namespace SNOWGIRL_CORE\Db;

use SNOWGIRL_CORE\Query;
use SNOWGIRL_CORE\Query\Expression;

class NullDb implements DbInterface
{
    public function getSchema(): ?string
    {
        return null;
    }

    public function insertOne(string $table, array $values, Query $query = null): bool
    {
        return 0;
    }

    public function replaceOne(string $table, array $values, Query $query = null): bool
    {
        return 0;
    }

    public function insertMany(string $table, array $values, Query $query = null): int
    {
        return 0;
    }

    public function selectOne(string $table, Query $query = null): ?array
    {
        return null;
    }

    public function selectMany(string $table, Query $query = null): array
    {
        return [];
    }

    public function selectCount(string $table, Query $query = null)
    {
        return 0;
    }

    public function selectFromEachGroup(string $table, $group, int $perGroup, Query $query = null): array
    {
        return [];
    }

    public function updateOne(string $table, array $values, Query $query = null): bool
    {
        return false;
    }

    public function updateMany(string $table, array $values, Query $query = null): int
    {
        return 0;
    }

    public function deleteOne(string $table, Query $query = null): bool
    {
        return false;
    }

    public function deleteMany(string $table, Query $query = null): int
    {
        return 0;
    }

    public function deleteFromEachGroup(string $table, $group, int $perGroup, Query $query = null, $joinOn = null, $inverse = false): int
    {
        return 0;
    }


    public function isTransactionOpened(): ?bool
    {
        return null;
    }

    public function openTransaction(): bool
    {
        return false;
    }

    public function commitTransaction(): bool
    {
        return false;
    }

    public function rollBackTransaction(): bool
    {
        return false;
    }

    public function makeTransaction(callable $fnOk, $default = null)
    {
        return $default;
    }


    public function req($query): DbInterface
    {
        return $this;
    }

    public function reqToArrays($query, $key = null): array
    {
        return [];
    }

    public function reqToArray($query): array
    {
        return [];
    }

    public function reqToObjects($query, $className, $key = null): array
    {
        return [];
    }


    public function makeSelectSQL($columns = '*', $isFoundRows = false, array &$params = [], $table = null): string
    {
        return '';
    }

    public function makeDistinctExpression($column, $table = null): Expression
    {
        return new Expression('');
    }

    public function makeFromSQL($tables): string
    {
        return '';
    }

    public function makeJoinSQL($join, array &$params): string
    {
        return '';
    }

    public function makeIndexSQL($index): string
    {
        return '';
    }

    public function makeWhereSQL($where, array &$params, $table = null, $placeholders = false): string
    {
        return '';
    }

    public function makeGroupSQL($group, array &$params, $table = null): string
    {
        return '';
    }

    public function makeOrderSQL($order, array &$params, $table = null): string
    {
        return '';
    }

    public function makeLimitSQL($offset, $limit, array &$params): string
    {
        return '';
    }

    public function makeWhereClauseSQL($where, array &$params, $table = null, $placeholders = false): string
    {
        return '';
    }

    public function makeHavingSQL($where, array &$params, $table = null, $placeholders = false): string
    {
        return '';
    }

    public function makeQuery($rawQuery, bool $isLikeInsteadMatch = false): string
    {
        return '';
    }

    public function quote($v, $table = null): string
    {
        return '';
    }


    public function foundRows(): int
    {
        return 0;
    }

    public function affectedRows(): int
    {
        return 0;
    }

    public function numRows(): int
    {
        return 0;
    }

    public function insertedId()
    {
        return null;
    }


    public function getManager(): DbManagerInterface
    {
        // TODO: Implement getManager() method.
    }
}