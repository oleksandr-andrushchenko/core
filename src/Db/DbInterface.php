<?php

namespace SNOWGIRL_CORE\Db;

use SNOWGIRL_CORE\Query;
use SNOWGIRL_CORE\Query\Expression;

interface DbInterface
{
    public function getSchema(): ?string;

    public function insertOne(string $table, array $values, Query $query = null): bool;

    public function insertMany(string $table, array $values, Query $query = null): int;

    public function selectOne(string $table, Query $query = null): ?array;

    public function selectMany(string $table, Query $query = null): array;

    public function selectCount(string $table, Query $query = null);

    public function selectFromEachGroup(string $table, $group, int $perGroup, Query $query = null): array;

    public function updateOne(string $table, array $values, Query $query = null): bool;

    public function updateMany(string $table, array $values, Query $query = null): int;

    public function deleteOne(string $table, Query $query = null): bool;

    public function deleteMany(string $table, Query $query = null): int;

    public function deleteFromEachGroup(string $table, $group, int $perGroup, Query $query = null, $joinOn = null, $inverse = false): int;


    public function isTransactionOpened(): ?bool;

    public function openTransaction(): bool;

    public function commitTransaction(): bool;

    public function rollBackTransaction(): bool;

    public function makeTransaction(callable $fnOk, $default = null);


    public function req($query): DbInterface;

    public function reqToArrays($query, $key = null): array;

    public function reqToArray($query): array;

    public function reqToObjects($query, $className, $key = null): array;


    public function makeSelectSQL($columns = '*', $isFoundRows = false, array &$params, $table = null): string;

    public function makeDistinctExpression($column, $table = null): Expression;

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


    public function getManager(): DbManagerInterface;
}