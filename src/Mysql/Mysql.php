<?php

namespace SNOWGIRL_CORE\Mysql;

use Psr\Log\LoggerInterface;
use SNOWGIRL_CORE\Helper\Arrays;
use mysqli;
use mysqli_stmt;
use mysqli_result;
use Throwable;
use SNOWGIRL_CORE\Mysql\MysqlException as Exception;

class Mysql implements MysqlInterface
{
    private $host;
    private $port;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var mysqli
     */
    private $mysqli;

    /**
     * @var mysqli_result
     */
    private $req;

    private $schema;
    private $user;
    private $password;
    private $socket;
    private $isTransactionOpened;

    /**
     * @var mysqli_stmt
     */
    private $stmt;

    public function __construct(string $host, int $port, string $schema, string $user, string $password, string $socket, LoggerInterface $logger)
    {
        $this->host = $host;
        $this->port = $port;
        $this->schema = $schema;
        $this->user = $user;
        $this->password = $password;
        $this->socket = $socket;
        $this->logger = $logger;
    }

    public function __destruct()
    {
        if (null !== $this->mysqli) {
            $this->logger->debug(__FUNCTION__);
            $this->dropMySQLiResult();
            $this->dropMySQLiSTMT();
            $this->dropMySQLi();
        }
    }

    public function getSchema(): ?string
    {
        return $this->schema;
    }

    /**
     * @param string $table
     * @param array $values
     * @param MysqlQuery|null $query
     * @return bool
     * @throws Exception
     */
    public function insertOne(string $table, array $values, MysqlQuery $query = null): bool
    {
        if (!count($values)) {
            return 0;
        }

        $query = MysqlQuery::normalize($query);

        $query->params = array_values($values);
        $query->text = implode(' ', [
            'INSERT' . ($query->ignore ? ' IGNORE ' : '') . ' INTO ' . $this->quote($table),
            '(' . implode(', ', array_map(function ($key) {
                return $this->quote($key);
            }, array_keys($values))) . ')',
            'VALUES',
            '(' . implode(',', array_fill(0, count($query->params), '?')) . ')',
        ]);

        return 1 == $this->req($query)->affectedRows();
    }

    public function replaceOne(string $table, array $values, MysqlQuery $query = null): bool
    {
        if (!count($values)) {
            return 0;
        }

        $query = MysqlQuery::normalize($query);

        $query->params = array_values($values);
        $query->text = implode(' ', [
            'REPLACE INTO ' . $this->quote($table),
            '(' . implode(', ', array_map(function ($key) {
                return $this->quote($key);
            }, array_keys($values))) . ')',
            'VALUES',
            '(' . implode(',', array_fill(0, count($query->params), '?')) . ')',
        ]);

        return 1 == $this->req($query)->affectedRows();
    }

    /**
     * @param string $table
     * @param array $values
     * @param MysqlQuery|null $query
     * @return int
     * @throws Exception
     */
    public function insertMany(string $table, array $values, MysqlQuery $query = null): int
    {
        if (!count($values)) {
            return 0;
        }

        $output = 0;

        $query = MysqlQuery::normalize($query);

        if (!is_array($query->params)) {
            $query->params = [];
        }

        $keys = array_keys($values[0]);

        $queryKeys = implode(', ', array_map(function ($key) {
            return $this->quote($key);
        }, $keys));

        //@todo test...
        $count = count($keys);
        $maxPerQuery = max($this->getMaxPerQueryPlaceholdersCount(), $count);

        foreach (array_chunk($values, floor($maxPerQuery / $count)) as $values2) {
            $query->params = [];

            $queryValues = [];

            foreach ($values2 as $v) {
                $tmp = [];

                foreach ($keys as $k) {
                    $tmp[] = '?';
                    $query->params[] = $v[$k];
                }

                $queryValues[] = '(' . implode(',', $tmp) . ')';
            }

            $query->text = implode(' ', [
                'INSERT' . ($query->ignore ? ' IGNORE ' : '') . ' INTO ' . $this->quote($table),
                '(' . $queryKeys . ')',
                'VALUES ' . implode(',', $queryValues),
            ]);

            $output += $this->req($query)->affectedRows();
        }

        return $output;
    }

    /**
     * @param string $table
     * @param MysqlQuery|null $query
     * @return array|null
     * @throws Exception
     */
    public function selectOne(string $table, MysqlQuery $query = null): ?array
    {
        $query = MysqlQuery::normalize($query);
        $query->limit = 1;

        return ($tmp = $this->selectMany($table, $query)) ? $tmp[0] : null;
    }


    /**
     * @param string $table
     * @param MysqlQuery|null $query
     * @return array
     * @throws Exception
     */
    public function selectMany(string $table, MysqlQuery $query = null): array
    {
        $query = MysqlQuery::normalize($query);

        if (!is_array($query->params)) {
            $query->params = [];
        }

        $query->text = implode(' ', [
            $this->makeSelectSQL($query->columns, $query->foundRows, $query->params),
            $this->makeFromSQL($table),
            $this->makeIndexSQL($query->indexes),
            $this->makeJoinSQL($query->joins, $query->params),
            $this->makeWhereSQL($query->where, $query->params, null, $query->placeholders),
            $this->makeGroupSQL($query->groups, $query->params),
            $this->makeOrderSQL($query->orders, $query->params),
            $this->makeHavingSQL($query->havings, $query->params),
            $this->makeLimitSQL($query->offset, $query->limit, $query->params),
        ]);

        return $this->reqToArrays($query);
    }

    /**
     * @param string $table
     * @param MysqlQuery|null $query
     * @return array|int
     * @throws Exception
     */
    public function selectCount(string $table, MysqlQuery $query = null)
    {
        $query = MysqlQuery::normalize($query);

        if (!is_array($query->params)) {
            $query->params = [];
        }

        if ($query->groups) {
            $query->columns[] = $query->groups;
        }

        $query->columns[] = new MysqlQueryExpression('COUNT(*) AS ' . $this->quote('cnt'));

        $tmp = $this->selectMany($table, $query);

        if ($query->groups) {
            $output = [];

            foreach ($tmp as $i) {
                $output[$i[$query->groups]] = (int) $i['cnt'];
            }

            return $output;
        }

        $output = (int) $tmp[0]['cnt'];

        return $output;
    }

    /**
     * @param string $table
     * @param $group
     * @param int $perGroup
     * @param MysqlQuery|null $query
     * @param bool $inverse
     * @return array
     * @throws Exception
     */
    public function selectFromEachGroup(string $table, $group, int $perGroup, MysqlQuery $query = null, $inverse = false): array
    {
        $query = MysqlQuery::normalize($query);

        $query->columns = Arrays::cast($query->columns);
        $query->columns[] = $group;

        if (!is_array($query->params)) {
            $query->params = [];
        }

        $query->text = implode(' ', [
            $this->makeSelectSQL($query->columns, $query->foundRows, $query->params),
            'FROM (',
            $this->makeSelectSQL(array_merge(array_unique(array_merge($query->columns, [$group])), [
                new MysqlQueryExpression('@rank := IF(@current = ' . $this->quote($group) . ', @rank + 1, 1) AS ' . $this->quote('rank')),
                new MysqlQueryExpression('@current := ' . $this->quote($group)),
            ]), false, $query->params),
            $this->makeFromSQL($table),
            $this->makeWhereSQL($query->where, $query->params, null, $query->placeholders),
            $this->makeOrderSQL(array_merge([$group], Arrays::cast($query->orders)), $query->params),
            ') AS ' . $this->quote('ranked'),
            $this->makeWhereSQL(new MysqlQueryExpression($this->quote('rank') . ' ' . ($inverse ? '>' : '<=') . ' ' . $perGroup), $query->params, null, $query->placeholders),
        ]);

        $rows = $this->reqToArrays($query);

        $output = [];

        foreach ($rows as $row) {
            if (!isset($output[$row[$group]])) {
                $output[$row[$group]] = [];
            }

            $output[$row[$group]][] = $row;
        }

        return $output;
    }

    /**
     * @param string $table
     * @param array $values
     * @param MysqlQuery|null $query
     * @return bool
     * @throws Exception
     */
    public function updateOne(string $table, array $values, MysqlQuery $query = null): bool
    {
        $query = MysqlQuery::normalize($query);
        $query->limit = 1;

        return 1 == $this->updateMany($table, $values, $query);
    }

    /**
     * @param $table
     * @param array $values
     * @param MysqlQuery|null $query
     * @return int
     * @throws Exception
     */
    public function updateMany($table, array $values, MysqlQuery $query = null): int
    {
        $query = MysqlQuery::normalize($query);

        $set = [];

        foreach ($values as $k => $v) {
            if ($v instanceof MysqlQueryExpression) {
                if (is_string($k)) {
                    $v->setQuery($this->quote($k) . ' = ' . $v->getQuery());
                }

                $v->addTo($set, $query->params);
            } else {
                $set[] = $this->quote($k) . ' = ?';
                $query->params[] = $v;
            }
        }

        $query->text = implode(' ', [
            'UPDATE ' . ($query->ignore ? 'IGNORE ' : '') . $this->quote($table),
            'SET ' . implode(', ', $set),
            $this->makeWhereSQL($query->where, $query->params, null, $query->placeholders),
        ]);

        return $this->req($query)->affectedRows();
    }

    /**
     * @param string $table
     * @param MysqlQuery|null $query
     * @return bool
     * @throws Exception
     */
    public function deleteOne(string $table, MysqlQuery $query = null): bool
    {
        $query = MysqlQuery::normalize($query);
        $query->limit = 1;

        return 1 == $this->deleteMany($table, $query);
    }

    /**
     * @param $table
     * @param MysqlQuery|null $query
     * @return int
     * @throws Exception
     */
    public function deleteMany($table, MysqlQuery $query = null): int
    {
        $query = MysqlQuery::normalize($query);

        if (!is_array($query->params)) {
            $query->params = [];
        }

        $query->text = implode(' ', [
            'DELETE ' . 'FROM ' . $this->quote($table),
            $this->makeWhereSQL($query->where, $query->params, null, $query->placeholders),
        ]);

        return $this->req($query)->affectedRows();
    }

    /**
     * @param string $table
     * @param $group
     * @param int $perGroup
     * @param MysqlQuery|null $query
     * @param null $joinOn
     * @param bool $inverse
     * @return int
     * @throws Exception
     */
    public function deleteFromEachGroup(string $table, $group, int $perGroup, MysqlQuery $query = null, $joinOn = null, $inverse = false): int
    {
        $query = MysqlQuery::normalize($query);

        $query->columns = Arrays::cast($query->columns);
        $query->columns[] = $group;

        if (!is_array($query->params)) {
            $query->params = [];
        }

        $joinOn = Arrays::cast($joinOn);
        $query->columns = array_unique(array_merge($joinOn, $query->columns));
        $joinOn[] = $group;

        $query->text = implode(' ', [
            'DELETE ' . $this->quote('t1'),
            'FROM ' . $this->quote($table) . ' AS ' . $this->quote('t1'),
            'INNER JOIN (',
            $this->makeSelectSQL($query->columns, $query->foundRows, $query->params),
            'FROM (',
            $this->makeSelectSQL(array_merge(array_unique(array_merge($query->columns, [$group])), [
                new MysqlQueryExpression('@rank := IF(@current = ' . $this->quote($group) . ', @rank + 1, 1) AS ' . $this->quote('rank')),
                new MysqlQueryExpression('@current := ' . $this->quote($group)),
            ]), false, $query->params),
            $this->makeFromSQL($table),
            $this->makeWhereSQL($query->where, $query->params, null, $query->placeholders),
            $this->makeOrderSQL(array_merge([$group], Arrays::cast($query->orders)), $query->params),
            ') AS ' . $this->quote('ranked'),
            $this->makeWhereSQL(new MysqlQueryExpression($this->quote('rank') . ' ' . ($inverse ? '>' : '<=') . ' ' . $perGroup), $query->params, null, $query->placeholders),
            ') ' . $this->quote('t2') . ' ON',
            implode(' AND ', array_map(function ($joinColumn) {
                return $this->quote($joinColumn, 't1') . ' = ' . $this->quote($joinColumn, 't2');
            }, $joinOn)),
        ]);

        return $this->req($query)->affectedRows();
    }


    public function isTransactionOpened(): ?bool
    {
        return $this->isTransactionOpened;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function openTransaction(): bool
    {
        $this->logger->debug(__FUNCTION__);

        if ($this->isTransactionOpened()) {
            $this->logger->warning('Previous transaction is not closed');
        }

        $this->isTransactionOpened = true;

        return $this->getClient()->autocommit(false);
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function commitTransaction(): bool
    {
        $this->logger->debug(__FUNCTION__);

        $this->isTransactionOpened = false;

        $output = $this->getClient()->commit();
        $this->getClient()->autocommit(true);

        return $output;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function rollBackTransaction(): bool
    {
        $this->logger->debug(__FUNCTION__);

        $this->isTransactionOpened = false;

        $output = $this->getClient()->rollback();
        $this->getClient()->autocommit(true);

        return $output;
    }

    /**
     * @param callable $fnOk
     * @param null $default
     * @return null
     * @throws Exception
     */
    public function makeTransaction(callable $fnOk, $default = null)
    {
        $this->openTransaction();

        try {
            $output = $fnOk($this);
            $this->commitTransaction();
        } catch (Throwable $e) {
            $this->logger->error($e);
            $this->rollBackTransaction();
            $output = $default;
        }

        return $output;
    }


    /**
     * @param string|MysqlQuery $query
     * @return MysqlInterface
     * @throws Exception
     */
    public function req($query): MysqlInterface
    {
        $query = MysqlQuery::normalize($query);

        if ($query->log) {
            $this->logger->debug($query);
        }

        $this->dropMySQLiResult();
        $this->dropMySQLiSTMT();

        if ($query->params) {
            $this->stmt = $this->getClient()->stmt_init();

            if (!$this->stmt->prepare($query->text)) {
                throw new Exception($this->stmt->error, $this->stmt->errno);
            }

            array_unshift($query->params, str_repeat('s', count($query->params)));
            $tmp = [];

            foreach ($query->params as $k => &$value) {
                $tmp[$k] = &$value;
            }

            if (!call_user_func_array([$this->stmt, 'bind_param'], $tmp)) {
                throw new Exception($this->stmt->error, $this->stmt->errno);
            }

            if (!$this->stmt->execute()) {
                throw new Exception($this->stmt->error, $this->stmt->errno);
            }

            $output = $this->stmt->get_result();

            if ($this->stmt->error) {
                throw new Exception($this->stmt->error, $this->stmt->errno);
            }
        } else {
            $output = $this->getClient()->query($query->text);

            if (false === $output) {
                throw new Exception($this->mysqli->error, $this->mysqli->errno);
            }
        }

        $this->req = $output;

        return $this;
    }

    /**
     * @param $query
     * @param null $key
     * @return array
     * @throws Exception
     */
    public function reqToArrays($query, $key = null): array
    {
        $this->req($query);

        $output = [];

        if ($key) {
//            $this->req->data_seek(0);
            while ($row = $this->req->fetch_array(MYSQLI_ASSOC)) {
                $output[$row[$key]] = $row;
            }
        } else {
            $output = $this->req->fetch_all(MYSQLI_ASSOC);
        }

        return $output;
    }

    /**
     * @param $query
     * @return array
     * @throws Exception
     */
    public function reqToArray($query): array
    {
        $this->req($query);

        $this->req->data_seek(0);

        while ($row = $this->req->fetch_array(MYSQLI_ASSOC)) {
            return $row;
        }

        return [];
    }

    /**
     * @param $query
     * @param $className
     * @param null $key
     * @return array
     * @throws Exception
     */
    public function reqToObjects($query, $className, $key = null): array
    {
        $this->req($query);

        $output = [];

        $this->req->data_seek(0);

        if ($key) {
            while ($row = $this->req->fetch_object($className)) {
                $output[$row[$key]] = $row;
            }
        } else {
            while ($row = $this->req->fetch_object($className)) {
                $output[] = $row;
            }
        }

        return $output;
    }


    public function makeSelectSQL($columns = '*', $isFoundRows = false, array &$params = [], $table = null): string
    {
        if (!$columns) {
            $columns = ['*'];
        }

        if (!is_array($columns)) {
            $columns = [$columns];
        }

        $query = [];

        foreach ($columns as $v) {
            if ('*' == $v) {
                $query[] = $v;
            } elseif ($v instanceof MysqlQueryExpression) {
                $v->addTo($query, $params);
            } else {
                $query[] = $this->quote($v, $table);
            }
        }

        //@todo transfer to mysql engine only...
        return 'SELECT ' . ($isFoundRows ? 'SQL_CALC_FOUND_ROWS' : '') . ' ' . implode(', ', $query);
    }

    public function makeDistinctExpression($column, $table = null): MysqlQueryExpression
    {
        return new MysqlQueryExpression('DISTINCT ' . $this->quote($column, $table));
    }

    public function makeFromSQL($tables): string
    {
        $tables = (array) $tables;
        return 'FROM ' . implode(', ', array_map(function ($alias, $table) {
                return $this->quote($table) . (is_string($alias) ? (' AS ' . $this->quote($alias)) : '');
            }, array_keys($tables), $tables));
    }

    public function makeJoinSQL($join, array &$params): string
    {
        if (!$join) {
            return '';
        }

        if ($join) {
            if (!is_array($join)) {
                $join = [$join];
            }

            $query = [];

            foreach ($join as $options) {
                if (is_array($options)) {
                    $table1 = $options[0];
                    $table2 = $options[1];
                    $queryTable = $this->quote($table2);

                    $queryOn = [];
                    $options[2] = is_array($options[2]) ? $options[2] : [$options[2]];

                    foreach ($options[2] as $i => $on) {
                        if (is_int($i)) {
                            $queryOn[] = $this->quote($on, $table1) . ' = ' . $this->quote($on, $table2);
                        } elseif (is_string($i)) {
                            $queryOn[] = $this->quote($i, $table1) . ' = ' . $this->quote($on, $table2);
                        } elseif ($on instanceof MysqlQueryExpression) {
                            $on->addTo($queryOn, $params);
                        }
                    }

                    $queryOn = implode(' AND ', $queryOn);
                    $queryType = isset($options[3]) ? ($options[3] . ' ') : '';

                    $query[] = $queryType . 'JOIN ' . $queryTable . ' ON ' . $queryOn;
                } elseif ($options instanceof MysqlQueryExpression) {
                    $options->addTo($query, $params);
                }
            }

            return implode(' ', $query);
        }

        return '';
    }

    public function makeIndexSQL($index): string
    {
        return $index ? ('USE INDEX(' . implode(', ', array_map(function ($index) {
                return $this->quote($index);
            }, is_array($index) ? $index : [$index])) . ')') : '';
    }

    public function makeWhereSQL($where, array &$params, $table = null, $placeholders = false): string
    {
        return ($w = $this->makeWhereClauseSQL($where, $params, $table, $placeholders)) ? ('WHERE ' . $w) : '';
    }

    public function makeGroupSQL($group, array &$params, $table = null): string
    {
        if ($group) {
            if (!is_array($group)) {
                $group = [$group];
            }

            $query = [];

            foreach ($group as $v) {
                if ($v instanceof MysqlQueryExpression) {
                    $v->addTo($query, $params);
                } else {
                    $query[] = $this->quote($v, $table);
                }
            }

            return 'GROUP BY ' . implode(', ', $query);
        }

        return '';
    }

    public function makeOrderSQL($order, array &$params, $table = null): string
    {
        if (!$order) {
            return '';
        }

        $query = [];

        if (is_array($order)) {
            $map = [
                SORT_ASC => 'ASC',
                SORT_DESC => 'DESC',
            ];

            foreach ($order as $k => $v) {
                $k = $this->quote($k, $table);

                if ($v instanceof MysqlQueryExpression) {
                    $v->addTo($query, $params);
                } else {
                    if (is_int($k)) {
                        $query[] = "$v";
                    } elseif (isset($map[$v])) {
                        $query[] = "$k {$map[$v]}";
                    } else {
                        $this->logger->warning('invalid sort direction has been passed in ' . __FUNCTION__, [
                            'key' => $k,
                            'value' => $v,
                        ]);
                    }
                }
            }
        } elseif ($order instanceof MysqlQueryExpression) {
            $order->addTo($query, $params);
        } elseif ('random' == $order) {
            $query[] = 'RAND()';
        } else {
            $this->logger->warning('invalid order has been passed in ' . __FUNCTION__, [
                'sort' => var_export($order, true),
            ]);
            return '';
        }

        return 'ORDER BY ' . implode(', ', $query);
    }

    public function makeLimitSQL($offset, $limit, array &$params): string
    {
        $tmp = [];

        if ($offset) {
            $params[] = $offset;
            $tmp[] = '?';
        }

        if ($limit) {
            $params[] = $limit;
            $tmp[] = '?';
        }

        if ($tmp) {
            return 'LIMIT ' . implode(',', $tmp);
        }

        return '';
    }

    public function makeWhereClauseSQL($where, array &$params, $table = null, $placeholders = false): string
    {
        if (!$where) {
            return '';
        }

        if (!is_array($where)) {
            $where = [$where];
        }

        $query = [];

        foreach ($where as $k => $v) {
            if ($v instanceof MysqlQueryExpression) {
                $v->addTo($query, $params, true);
            } else {
                $k = $this->quote($k, $table);

                if (is_array($v)) {
                    $s = count($v);

                    if ($s > 0) {
                        $v = array_values($v);

                        if (1 == $s) {
                            if ($placeholders) {
                                $query[] = $k . ' = ?';
                                $params[] = $v[0];
                            } else {
                                $query[] = $k . ' = \'' . $v[0] . '\'';
                            }
                        } else {
                            if ($placeholders) {
                                $query[] = $k . ' IN (' . implode(', ', array_fill(0, $s, '?')) . ')';
                                $params = array_merge($params, $v);
                            } else {
                                $query[] = $k . ' IN (\'' . implode('\', \'', $v) . '\')';
                            }
                        }
                    }
                } elseif (null === $v) {
                    $query[] = $k . ' IS NULL';
                } else {
                    if ($placeholders) {
                        $query[] = $k . ' = ?';
                        $params[] = $v;
                    } else {
                        $query[] = $k . ' = \'' . $v . '\'';
                    }
                }
            }
        }

        return implode(' AND ', $query);
    }

    public function makeHavingSQL($where, array &$params, $table = null, $placeholders = false): string
    {
        return ($w = $this->makeWhereClauseSQL($where, $params, $table, $placeholders)) ? ('HAVING ' . $w) : '';
    }

    public function makeQuery($rawQuery, bool $isLikeInsteadMatch = false): string
    {
        $output = trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $rawQuery));

        if ($v = preg_split('/[\s,-]+/', $output, 5)) {
            $tmp = [];
            $s = count($v);

            foreach ($v as $kk => $vv) {
                if ($kk < $s - 1) {
                    $tmp[] = $vv;
                } else {
                    $tmp[] = ($isLikeInsteadMatch ? '%' : '*') . $vv . ($isLikeInsteadMatch ? '%' : '*');
                }
            }

            $output = implode(' ', $tmp);
        }

        return $output;
    }

    public function quote($v, $table = null): string
    {
        if (is_array($v)) {
            foreach ($v as &$k) {
                $k = $this->quote($k, $table);
            }
        }

        if ($table) {
            $table = "`$table`.";
        }

        if ('*' != $v) {
            $v = "`$v`";
        }

        return $table . $v;
    }


    /**
     * @return int
     * @throws Exception
     */
    public function foundRows(): int
    {
        return (int) $this->reqToArray('SELECT FOUND_ROWS() AS ' . $this->quote('c'))['c'];
    }

    public function numRows(): int
    {
        return $this->req->num_rows;
    }

    public function affectedRows(): int
    {
        if ($this->stmt instanceof mysqli_stmt) {
            return $this->stmt->affected_rows;
        }

        if ($this->mysqli instanceof mysqli) {
            return $this->mysqli->affected_rows;
        }

        $this->logger->debug('can\'t fetch affected_rows');
        return 0;
    }

    public function insertedId()
    {
        if ($this->stmt instanceof mysqli_stmt) {
            return $this->stmt->insert_id;
        }

        if ($this->mysqli instanceof mysqli) {
            return $this->mysqli->insert_id;
        }

        $this->logger->debug('can\'t fetch insert_id');

        return null;
    }

    /**
     * Should be synced with self::indexExists()
     * @param string $table
     * @param $column
     * @param string|null $key
     * @param bool $unique
     * @return bool
     * @throws Exception
     */
    public function addTableKey(string $table, $column, string $key = null, bool $unique = false): bool
    {
        if (!is_array($column)) {
            $column = [$column];
        }

        if (null === $key) {
            $key = implode('_', $column);
        }

        $this->req(implode(' ', [
            'ALTER TABLE',
            $this->quote($table),
            'ADD' . ($unique ? ' UNIQUE' : '') . ' KEY',
            $this->quote($this->normalizeKey($key, $unique)),
            '(' . implode(', ', array_map([$this, 'quote'], $column)) . ')',
        ]));

        return true;
    }

    public function dropTableKey(string $table, string $key, bool $unique = false): bool
    {
        $this->req(implode(' ', [
            'ALTER TABLE', $this->quote($table),
            'DROP KEY',
            'IF EXISTS',
            $this->quote($this->normalizeKey($key, $unique)),
        ]));

        return true;
    }

    public function getTables(): array
    {
        $output = [];

        foreach ($this->reqToArrays('SHOW TABLES') as $table) {
            $output[] = $table['Tables_in_' . $this->getSchema()];
        }

        return $output;
    }

    public function getColumns(string $table): array
    {
        $output = [];

        foreach ($this->reqToArrays('SHOW COLUMNS FROM ' . $this->quote($table)) as $row) {
            $output[] = $row['Field'];
        }

        return $output;
    }

    public function showCreateTable(string $table, bool $ifNotExists = false, bool $autoIncrement = false): string
    {
        $createTable = 'CREATE TABLE';

        if (!$tmp = $this->reqToArray('SHOW ' . $createTable . ' ' . $this->quote($table))) {
            return '';
        }

        if (!isset($tmp['Create Table'])) {
            return '';
        }

        $output = $tmp['Create Table'];

        if ($ifNotExists) {
            $output = str_replace($createTable, $createTable . ' IF NOT EXISTS', $output);
        }

        if (!$autoIncrement) {
            $output = preg_replace('/ AUTO_INCREMENT=[0-9]+/', '', $output);
        }

        return $output;
    }

    public function showCreateColumn(string $table, string $column): string
    {
        if (!$showCreateTable = $this->showCreateTable($table)) {
            return '';
        }

        foreach (explode("\n", $showCreateTable) as $row) {
            if (false !== strpos($row, $this->quote($column))) {
                return trim(rtrim(trim($row), ','));
            }
        }

        return '';
    }

    public function columnExists(string $table, string $column): bool
    {
        return in_array($column, $this->getColumns($table));
    }

    /**
     * Should be synced with self::addTableKey()
     * @param string $table
     * @param string $column
     * @param string|null $key
     * @param bool $unique
     * @return bool
     */
    public function indexExists(string $table, $column, string $key = null, bool $unique = false): bool
    {
        if (!is_array($column)) {
            $column = [$column];
        }

        if (null === $key) {
            $key = implode('_', $column);
        }

        $normalizedKey = $this->normalizeKey($key, $unique);
        $normalizedKeys = $this->getIndexes($table);

        return array_key_exists($normalizedKey, $normalizedKeys) && ([] === array_diff($normalizedKeys[$normalizedKey], $column));
    }

    public function getIndexes(string $table): array
    {
        $output = [];

        foreach ($this->reqToArrays('SHOW INDEX FROM ' . $this->quote($table)) as $tmp) {
            $index = $tmp['Key_name'];

            if (!isset($output[$index])) {
                $output[$index] = [];
            }

            $output[$index][--$tmp['Seq_in_index']] = $tmp['Column_name'];
        }

        return $output;
    }

    public function createTable(string $table, array $records, string $engine = 'InnoDB', string $charset = 'utf8', bool $temporary = false): bool
    {
        $this->req(implode(' ', [
            'CREATE ' . ($temporary ? 'TEMPORARY ' : '') . 'TABLE IF NOT EXISTS',
            $this->quote($table),
            "(\r\n",
            implode(",\r\n", $records),
            "\r\n)",
            'ENGINE=' . $engine,
            'DEFAULT CHARSET=' . $charset,
        ]));

        return true;
    }

    public function createLikeTable(string $table, string $newTable): bool
    {
        $this->req(implode(' ', ['CREATE TABLE', $this->quote($newTable), 'LIKE', $this->quote($table)]));

        return true;
    }

    public function addTableColumn(string $table, string $column, $options): bool
    {
        $this->req(implode(' ', ['ALTER TABLE', $this->quote($table), 'ADD COLUMN', $this->quote($column), $options]));

        return true;
    }

    public function dropTableColumn(string $table, string $column): bool
    {
        $this->req(implode(' ', ['ALTER TABLE', $this->quote($table), 'DROP', 'IF EXISTS', $this->quote($column)]));

        return true;
    }

    public function renameTable(string $table, string $newTable): bool
    {
        $this->req(implode(' ', ['RENAME TABLE', $this->quote($table), 'TO', $this->quote($newTable)]));

        return true;
    }

    public function truncateTable(string $table): bool
    {
        $this->req(implode(' ', ['TRUNCATE TABLE', $this->quote($table)]));

        return true;
    }

    public function dropTable(string $table): bool
    {
        $this->req(implode(' ', ['DROP TABLE', 'IF EXISTS', $this->quote($table)]));

        return true;
    }

    /**
     * @return mysqli
     * @throws Exception
     */
    private function getClient(): mysqli
    {
        if (null === $this->mysqli) {
            $this->logger->debug(__FUNCTION__);

            $client = new mysqli($this->host, $this->user, $this->password, $this->schema, $this->port, $this->socket);

            if ($client->connect_error) {
                throw new Exception($client->connect_error, $client->connect_errno);
            }

            $client->set_charset('utf8');

            $this->mysqli = $client;
        }

        return $this->mysqli;
    }

    private function dropMySQLi()
    {
        if ($this->mysqli instanceof mysqli) {
            $this->mysqli->close();
        }

        $this->mysqli = null;
    }

    private function dropMySQLiResult()
    {
        if ($this->req instanceof mysqli_result) {
            $this->req->free();
        }

        $this->req = null;
    }

    private function dropMySQLiSTMT()
    {
        if ($this->stmt instanceof mysqli_stmt) {
            try {
                $this->stmt->close();
            } catch (Throwable $e) {
                $this->logger->error($e);
            }
        }

        $this->stmt = null;
    }

    private function getMaxPerQueryPlaceholdersCount()
    {
        return 65535;
    }

    private function normalizeKey(string $key, bool $unique = false)
    {
        $prefix = $unique ? 'uk_' : 'ix_';
        $key = $prefix . preg_replace('/^' . $prefix . '/', '', $key);
        return $key;
    }
}