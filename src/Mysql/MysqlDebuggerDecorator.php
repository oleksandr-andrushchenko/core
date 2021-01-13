<?php

namespace SNOWGIRL_CORE\Mysql;

use Psr\Log\LoggerInterface;
use SNOWGIRL_CORE\DebuggerTrait;

class MysqlDebuggerDecorator extends MysqlAbstractDecorator
{
    use DebuggerTrait;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Mysql $mysql, LoggerInterface $logger)
    {
        parent::__construct($mysql);

        $this->logger = $logger;
    }

    public function insertOne(string $table, array $values, MysqlQuery $query = null): bool
    {
        return $this->debug(__FUNCTION__, [$table, $values, $query]);
    }

    public function replaceOne(string $table, array $values, MysqlQuery $query = null): bool
    {
        return $this->debug(__FUNCTION__, [$table, $values, $query]);
    }

    public function insertMany(string $table, array $values, MysqlQuery $query = null): int
    {
        return $this->debug(__FUNCTION__, [$table, $values, $query]);
    }

    public function selectOne(string $table, MysqlQuery $query = null): ?array
    {
        return $this->debug(__FUNCTION__, [$table, $query]);
    }

    public function selectMany(string $table, MysqlQuery $query = null): array
    {
        return $this->debug(__FUNCTION__, [$table, $query]);
    }

    public function selectCount(string $table, MysqlQuery $query = null)
    {
        return $this->debug(__FUNCTION__, [$table, $query]);
    }

    public function selectFromEachGroup(string $table, $group, int $perGroup, MysqlQuery $query = null): array
    {
        return $this->debug(__FUNCTION__, [$table, $group, $perGroup, $query]);
    }

    public function updateOne(string $table, array $values, MysqlQuery $query = null): bool
    {
        return $this->debug(__FUNCTION__, [$table, $values, $query]);
    }

    public function updateMany(string $table, array $values, MysqlQuery $query = null): int
    {
        return $this->debug(__FUNCTION__, [$table, $values, $query]);
    }

    public function deleteOne(string $table, MysqlQuery $query = null): bool
    {
        return $this->debug(__FUNCTION__, [$table, $query]);
    }

    public function deleteMany(string $table, MysqlQuery $query = null): int
    {
        return $this->debug(__FUNCTION__, [$table, $query]);
    }

    public function deleteFromEachGroup(string $table, $group, int $perGroup, MysqlQuery $query = null, $joinOn = null, $inverse = false): int
    {
        return $this->debug(__FUNCTION__, [$table, $group, $perGroup, $query, $joinOn, $inverse]);
    }

    public function req($query): MysqlInterface
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