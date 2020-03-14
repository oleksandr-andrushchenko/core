<?php

namespace SNOWGIRL_CORE\Db;

use Psr\Log\LoggerInterface;
use SNOWGIRL_CORE\Helper\Arrays;
use SNOWGIRL_CORE\Query\Expression;
use SNOWGIRL_CORE\Query;
use mysqli;
use mysqli_stmt;
use mysqli_result;
use Throwable;
use SNOWGIRL_CORE\Db\DbException as Exception;

class MysqlDb implements DbInterface
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

    /** @var mysqli_stmt */
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
     * @param Query|null $query
     *
     * @return bool
     * @throws DbException
     */
    public function insertOne(string $table, array $values, Query $query = null): bool
    {
        if (!count($values)) {
            return 0;
        }

        $query = Query::normalize($query);

        $query->params = array_values($values);
        $query->text = implode(' ', [
            'INSERT' . ($query->ignore ? ' IGNORE ' : '') . ' INTO ' . $this->quote($table),
            '(' . implode(', ', array_map(function ($key) {
                return $this->quote($key);
            }, array_keys($values))) . ')',
            'VALUES',
            '(' . implode(',', array_fill(0, count($query->params), '?')) . ')'
        ]);

        return 1 == $this->req($query)->affectedRows();
    }

    /**
     * @param string $table
     * @param array $values
     * @param Query|null $query
     *
     * @return int
     * @throws DbException
     */
    public function insertMany(string $table, array $values, Query $query = null): int
    {
        if (!count($values)) {
            return 0;
        }

        $output = 0;

        $query = Query::normalize($query);

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
                'VALUES ' . implode(',', $queryValues)
            ]);

            $output += $this->req($query)->affectedRows();
        }

        return $output;
    }

    /**
     * @param string $table
     * @param Query|null $query
     *
     * @return array|null
     * @throws DbException
     */
    public function selectOne(string $table, Query $query = null): ?array
    {
        $query = Query::normalize($query);
        $query->limit = 1;

        return ($tmp = $this->selectMany($table, $query)) ? $tmp[0] : null;
    }


    /**
     * @param string $table
     * @param Query|null $query
     *
     * @return array
     * @throws DbException
     */
    public function selectMany(string $table, Query $query = null): array
    {
        $query = Query::normalize($query);

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
            $this->makeLimitSQL($query->offset, $query->limit, $query->params)
        ]);

        return $this->reqToArrays($query);
    }

    /**
     * @param string $table
     * @param Query|null $query
     *
     * @return array|int
     * @throws DbException
     */
    public function selectCount(string $table, Query $query = null)
    {
        $query = Query::normalize($query);

        if (!is_array($query->params)) {
            $query->params = [];
        }

        if ($query->groups) {
            $query->columns[] = $query->groups;
        }

        $query->columns[] = new Expression('COUNT(*) AS ' . $this->quote('cnt'));

        $tmp = $this->selectMany($table, $query);

        if ($query->groups) {
            $output = [];

            foreach ($tmp as $i) {
                $output[$i[$query->groups]] = (int)$i['cnt'];
            }

            return $output;
        }

        $output = (int)$tmp[0]['cnt'];

        return $output;
    }

    /**
     * @param string $table
     * @param $group
     * @param int $perGroup
     * @param Query|null $query
     * @param bool $inverse
     *
     * @return array
     * @throws DbException
     */
    public function selectFromEachGroup(string $table, $group, int $perGroup, Query $query = null, $inverse = false): array
    {
        $query = Query::normalize($query);

        $query->columns = Arrays::cast($query->columns);
        $query->columns[] = $group;

        if (!is_array($query->params)) {
            $query->params = [];
        }

        $query->text = implode(' ', [
            $this->makeSelectSQL($query->columns, $query->foundRows, $query->params),
            'FROM (',
            $this->makeSelectSQL(array_merge(array_unique(array_merge($query->columns, [$group])), [
                new Expression('@rank := IF(@current = ' . $this->quote($group) . ', @rank + 1, 1) AS ' . $this->quote('rank')),
                new Expression('@current := ' . $this->quote($group))
            ]), false, $query->params),
            $this->makeFromSQL($table),
            $this->makeWhereSQL($query->where, $query->params),
            $this->makeOrderSQL(array_merge([$group], Arrays::cast($query->orders)), $query->params),
            ') AS ' . $this->quote('ranked'),
            $this->makeWhereSQL(new Expression($this->quote('rank') . ' ' . ($inverse ? '>' : '<=') . ' ' . $perGroup), $query->params)
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
     * @param Query|null $query
     *
     * @return bool
     * @throws DbException
     */
    public function updateOne(string $table, array $values, Query $query = null): bool
    {
        $query = Query::normalize($query);
        $query->limit = 1;

        return 1 == $this->updateMany($table, $values, $query);
    }

    /**
     * @param $table
     * @param array $values
     * @param Query|null $query
     *
     * @return int
     * @throws DbException
     */
    public function updateMany($table, array $values, Query $query = null): int
    {
        $query = Query::normalize($query);

        $set = [];

        foreach ($values as $k => $v) {
            if ($v instanceof Expression) {
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
            $this->makeWhereSQL($query->where, $query->params)
        ]);

        return $this->req($query)->affectedRows();
    }

    /**
     * @param string $table
     * @param Query|null $query
     *
     * @return bool
     * @throws DbException
     */
    public function deleteOne(string $table, Query $query = null): bool
    {
        $query = Query::normalize($query);
        $query->limit = 1;

        return 1 == $this->deleteMany($table, $query);
    }

    /**
     * @param $table
     * @param Query|null $query
     *
     * @return int
     * @throws DbException
     */
    public function deleteMany($table, Query $query = null): int
    {
        $query = Query::normalize($query);

        if (!is_array($query->params)) {
            $query->params = [];
        }

        $query->text = implode(' ', [
            'DELETE ' . 'FROM ' . $this->quote($table),
            $this->makeWhereSQL($query->where, $query->params)
        ]);

        return $this->req($query)->affectedRows();
    }

    /**
     * @param string $table
     * @param $group
     * @param int $perGroup
     * @param Query|null $query
     * @param null $joinOn
     * @param bool $inverse
     *
     * @return int
     * @throws DbException
     */
    public function deleteFromEachGroup(string $table, $group, int $perGroup, Query $query = null, $joinOn = null, $inverse = false): int
    {
        $query = Query::normalize($query);

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
                new Expression('@rank := IF(@current = ' . $this->quote($group) . ', @rank + 1, 1) AS ' . $this->quote('rank')),
                new Expression('@current := ' . $this->quote($group))
            ]), false, $query->params),
            $this->makeFromSQL($table),
            $this->makeWhereSQL($query->where, $query->params),
            $this->makeOrderSQL(array_merge([$group], Arrays::cast($query->orders)), $query->params),
            ') AS ' . $this->quote('ranked'),
            $this->makeWhereSQL(new Expression($this->quote('rank') . ' ' . ($inverse ? '>' : '<=') . ' ' . $perGroup), $query->params),
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
     * @throws DbException
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
     * @throws DbException
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
     * @throws DbException
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
     *
     * @return null
     * @throws DbException
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
     * @param $query
     *
     * @return DbInterface
     * @throws DbException
     */
    public function req($query): DbInterface
    {
        $query = Query::normalize($query);

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
     *
     * @return array
     * @throws DbException
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
     *
     * @return array
     * @throws DbException
     */
    public function reqToArray($query): array
    {
        $this->req($query);

        $this->req->data_seek(0);

        while ($row = $this->req->fetch_array(MYSQLI_ASSOC)) {
            return $row;
        }

        return null;
    }

    /**
     * @param $query
     * @param $className
     * @param null $key
     *
     * @return array
     * @throws DbException
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


    public function makeSelectSQL($columns = '*', $isFoundRows = false, array &$params, $table = null): string
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
            } elseif ($v instanceof Expression) {
                $v->addTo($query, $params);
            } else {
                $query[] = $this->quote($v, $table);
            }
        }

        //@todo transfer to mysql engine only...
        return 'SELECT ' . ($isFoundRows ? 'SQL_CALC_FOUND_ROWS' : '') . ' ' . implode(', ', $query);
    }

    public function makeDistinctExpression($column, $table = null): Expression
    {
        return new Expression('DISTINCT ' . $this->quote($column, $table));
    }

    public function makeFromSQL($tables): string
    {
        $tables = (array)$tables;
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
                        } elseif ($on instanceof Expression) {
                            $on->addTo($queryOn, $params);
                        }
                    }

                    $queryOn = implode(' AND ', $queryOn);
                    $queryType = isset($options[3]) ? ($options[3] . ' ') : '';


                    $query[] = $queryType . 'JOIN ' . $queryTable . ' ON ' . $queryOn;
                } elseif ($options instanceof Expression) {
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
                if ($v instanceof Expression) {
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
        if ($order) {
            $query = [];

            if (is_array($order)) {
                $map = [
                    SORT_ASC => 'ASC',
                    SORT_DESC => 'DESC'
                ];

                foreach ($order as $k => $v) {
                    $k = $this->quote($k, $table);

                    if ($v instanceof Expression) {
                        $v->addTo($query, $params);
                    } else {
                        if (isset($map[$v])) {
                            $query[] = "$k {$map[$v]}";
                        } else {
                            $query[] = "$v";
                        }
                    }
                }
            } elseif ($order instanceof Expression) {
                $order->addTo($query, $params);
            } elseif ('random' == $order) {
                $query[] = 'RAND()';
            }

            return 'ORDER BY ' . implode(', ', $query);
        }

        return '';
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
            if ($v instanceof Expression) {
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
     * @throws DbException
     */
    public function foundRows(): int
    {
        return (int)$this->reqToArray('SELECT FOUND_ROWS() AS ' . $this->quote('c'))['c'];
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
        return null;
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

    public function getManager(): DbManagerInterface
    {
        return new MysqlDbManager($this);
    }

    /**
     * @return mysqli
     * @throws DbException
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
}