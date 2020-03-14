<?php

namespace SNOWGIRL_CORE\Db\Decorator;

use Psr\Log\LoggerInterface;
use SNOWGIRL_CORE\Db\DbInterface;
use SNOWGIRL_CORE\DebuggerTrait;
use SNOWGIRL_CORE\Query;

class DebuggerDbDecorator extends AbstractDbDecorator
{
    use DebuggerTrait;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(DbInterface $db, LoggerInterface $logger)
    {
        parent::__construct($db);

        $this->logger = $logger;
    }

    public function insertOne(string $table, array $values, Query $query = null): bool
    {
        return $this->debug(__FUNCTION__, [$table, $values, $query]);
    }

    public function insertMany(string $table, array $values, Query $query = null): int
    {
        return $this->debug(__FUNCTION__, [$table, $values, $query]);
    }

    public function selectOne(string $table, Query $query = null): ?array
    {
        return $this->debug(__FUNCTION__, [$table, $query]);
    }

    public function selectMany(string $table, Query $query = null): array
    {
        return $this->debug(__FUNCTION__, [$table, $query]);
    }

    public function selectCount(string $table, Query $query = null)
    {
        return $this->debug(__FUNCTION__, [$table, $query]);
    }

    public function selectFromEachGroup(string $table, $group, int $perGroup, Query $query = null): array
    {
        return $this->debug(__FUNCTION__, [$table, $group, $perGroup, $query]);
    }

    public function updateOne(string $table, array $values, Query $query = null): bool
    {
        return $this->debug(__FUNCTION__, [$table, $values, $query]);
    }

    public function updateMany(string $table, array $values, Query $query = null): int
    {
        return $this->debug(__FUNCTION__, [$table, $values, $query]);
    }

    public function deleteOne(string $table, Query $query = null): bool
    {
        return $this->debug(__FUNCTION__, [$table, $query]);
    }

    public function deleteMany(string $table, Query $query = null): int
    {
        return $this->debug(__FUNCTION__, [$table, $query]);
    }

    public function deleteFromEachGroup(string $table, $group, int $perGroup, Query $query = null, $joinOn = null, $inverse = false): int
    {
        return $this->debug(__FUNCTION__, [$table, $group, $perGroup, $query, $joinOn, $inverse]);
    }


    public function req($query): DbInterface
    {
        return $this->debug(__FUNCTION__, func_get_args());
    }

    public function reqToArrays($query, $key = null): array
    {
        return $this->debug(__FUNCTION__, [$query, $key]);
    }

    public function reqToArray($query): array
    {
        return $this->debug(__FUNCTION__, func_get_args());
    }

    public function reqToObjects($query, $className, $key = null): array
    {
        return $this->debug(__FUNCTION__, [$query, $className, $key]);
    }
}