<?php

namespace SNOWGIRL_CORE\Service;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\ObserverTrait;
use SNOWGIRL_CORE\Service\Storage\Query\Expr;
use SNOWGIRL_CORE\Service\Storage\Query;

abstract class Rdbms extends Storage
{
    use ObserverTrait;

    /**
     * @param            $what
     * @param array      $values
     * @param Query|null $query
     *
     * @return int
     */
    public function insertOne($what, array $values, Query $query = null)
    {
        if (!count($values)) {
            return 0;
        }

        $query = Query::normalize($query);

        $query->params = array_values($values);
        $query->text = implode(' ', [
            'INSERT' . ($query->ignore ? ' IGNORE ' : '') . ' INTO ' . $this->quote($what),
            '(' . implode(', ', array_map(function ($key) {
                return $this->quote($key);
            }, array_keys($values))) . ')',
            'VALUES',
            '(' . implode(',', array_fill(0, count($query->params), '?')) . ')'
        ]);

        return $this->req($query)->affectedRows();
    }

    public function insertMany($what, array $values, Query $query = null)
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
                'INSERT' . ($query->ignore ? ' IGNORE ' : '') . ' INTO ' . $this->quote($what),
                '(' . $queryKeys . ')',
                'VALUES ' . implode(',', $queryValues)
            ]);

            $output += $this->req($query)->affectedRows();
        }

        return $output;
    }

    /**
     * @param            $table
     * @param Query|null $query
     *
     * @return array
     */
    public function selectMany($table, Query $query = null)
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
            $this->makeWhereSQL($query->where, $query->params),
            $this->makeGroupSQL($query->groups, $query->params),
            $this->makeOrderSQL($query->orders, $query->params),
            $this->makeHavingSQL($query->havings, $query->params),
            $this->makeLimitSQL($query->offset, $query->limit, $query->params)
        ]);

        return $this->req($query)->reqToArrays();
    }

    /**
     * @param            $table
     * @param array      $values
     * @param Query|null $query
     *
     * @return int
     */
    public function updateMany($table, array $values, Query $query = null)
    {
        $query = Query::normalize($query);

        $set = [];

        foreach ($values as $k => $v) {
            if ($v instanceof Expr) {
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

    public function updateOne($table, array $values, Query $query = null)
    {
        return $this->updateMany(...func_get_args());
    }

    public function deleteMany($table, Query $query = null)
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

    public function deleteOne($table, Query $query = null)
    {
        return $this->deleteMany(...func_get_args());
    }

    /**
     * @param            $table
     * @param Query|null $query
     *
     * @return array|int|int[]
     */
    public function selectCount($table, Query $query = null)
    {
        $query = Query::normalize($query);

        if (!is_array($query->params)) {
            $query->params = [];
        }

        if ($query->groups) {
            $query->columns[] = $query->groups;
        }

        $query->columns[] = new Expr('COUNT(*) AS ' . $this->quote('cnt'));

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

    public const EVENT_COMPLETE = 'event_complete';

    protected $schema;

    public function getSchema()
    {
        return $this->schema;
    }

    protected $user;

    public function getUser()
    {
        return $this->user;
    }

    protected $password;

    public function getPassword()
    {
        return $this->password;
    }

    protected $socket;

    public function getSocket()
    {
        return $this->socket;
    }

    abstract public function getTables(): array;

    abstract public function getColumns(string $table): array;

    abstract public function selectFromEachGroup(string $table, $group, int $perGroup, Query $query = null);

    public function makeSelectSQL($columns = '*', $isFoundRows = false, array &$bind, $table = '')
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
            } elseif ($v instanceof Expr) {
                $v->addTo($query, $bind);
            } else {
                $query[] = $this->quote($v, $table);
            }
        }

        //@todo transfer to mysql engine only...
        return 'SELECT ' . ($isFoundRows ? 'SQL_CALC_FOUND_ROWS' : '') . ' ' . implode(', ', $query);
    }

    public function makeDistinctExpr($column, $table = '')
    {
        return new Expr('DISTINCT ' . $this->quote($column, $table));
    }

    /**
     * @todo... add tables aliases and implement
     *
     * @param $tables
     *
     * @return string
     */
    public function makeFromSQL($tables)
    {
        $tables = (array)$tables;
        return 'FROM ' . implode(', ', array_map(function ($alias, $table) {
                return $this->quote($table) . (is_string($alias) ? (' AS ' . $this->quote($alias)) : '');
            }, array_keys($tables), $tables));
    }

    public function makeJoinSQL($join, array &$bind)
    {
        if (!$join) {
            return null;
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
                        } elseif ($on instanceof Expr) {
                            $on->addTo($queryOn, $bind);
                        }
                    }

                    $queryOn = implode(' AND ', $queryOn);
                    $queryType = isset($options[3]) ? ($options[3] . ' ') : '';


                    $query[] = $queryType . 'JOIN ' . $queryTable . ' ON ' . $queryOn;
                } elseif ($options instanceof Expr) {
                    $options->addTo($query, $bind);
                }
            }

            return implode(' ', $query);
        }

        return '';
    }

    public function makeIndexSQL($index)
    {
        return $index ? ('USE INDEX(' . implode(', ', array_map(function ($index) {
                return $this->quote($index);
            }, is_array($index) ? $index : [$index])) . ')') : '';
    }

    public function makeWhereSQL($where, array &$bind, $table = '')
    {
        return ($w = $this->makeWhereClauseSQL($where, $bind, $table)) ? ('WHERE ' . $w) : '';
    }

    public function makeGroupSQL($group, array &$bind, $table = '')
    {
        if ($group) {
            if (!is_array($group)) {
                $group = [$group];
            }

            $query = [];

            foreach ($group as $v) {
                if ($v instanceof Expr) {
                    $v->addTo($query, $bind);
                } else {
                    $query[] = $this->quote($v, $table);
                }
            }

            return 'GROUP BY ' . implode(', ', $query);
        }

        return '';
    }

    public function makeOrderSQL($order, array &$bind, $table = '')
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

                    if ($v instanceof Expr) {
                        $v->addTo($query, $bind);
                    } else {
                        if (isset($map[$v])) {
                            $query[] = "$k {$map[$v]}";
                        } else {
                            $query[] = "$v";
                        }
                    }
                }
            } elseif ($order instanceof Expr) {
                $order->addTo($query, $bind);
            } elseif ('random' == $order) {
                $query[] = 'RAND()';
            }

            return 'ORDER BY ' . implode(', ', $query);
        }

        return '';
    }

    public static function makeLimitSQL($offset, $limit, array &$bind)
    {
        $tmp = [];

        if ($offset) {
            $bind[] = $offset;
            $tmp[] = '?';
        }

        if ($limit) {
            $bind[] = $limit;
            $tmp[] = '?';
        }

        if ($tmp) {
            return 'LIMIT ' . implode(',', $tmp);
        }

        return '';
    }

    public function makeWhereClauseSQL($where, array &$bind, $table = '')
    {
        if (!$where) {
            return null;
        }

        if (!is_array($where)) {
            $where = [$where];
        }

        $query = [];

        foreach ($where as $k => $v) {
            if ($v instanceof Expr) {
                $v->addTo($query, $bind, true);
            } else {
                $k = $this->quote($k, $table);

                if (is_array($v)) {
                    $s = count($v);

                    if ($s > 0) {
                        $v = array_values($v);

                        if (1 == $s) {
                            $query[] = $k . ' = ?';
                            $bind[] = $v[0];
                        } else {
                            $query[] = $k . ' IN (' . implode(', ', array_fill(0, $s, '?')) . ')';
                            $bind = array_merge($bind, $v);
                        }
                    }
                } elseif (null === $v) {
                    $query[] = $k . ' IS NULL';
                } else {
                    $query[] = $k . ' = ?';
                    $bind[] = $v;
                }
            }
        }

        return implode(' AND ', $query);
    }

    public function makeHavingSQL($where, array &$bind, $table = '')
    {
        return ($w = $this->makeWhereClauseSQL($where, $bind, $table)) ? ('HAVING ' . $w) : '';
    }

    abstract public function _quote($v, $table = '');

    public function quote($v, $table = '')
    {
        if (is_array($v)) {
            foreach ($v as &$k) {
                $k = $this->quote($k, $table);
            }

            return $v;
        }

        return $this->_quote($v, $table);
    }

    protected $isTransactionOpened;

    public function isTransactionOpened()
    {
        return $this->isTransactionOpened;
    }

    abstract protected function _openTransaction();

    public function openTransaction()
    {
        $this->log(__FUNCTION__);

        if ($this->isTransactionOpened()) {
            $this->log('Previous transaction is not closed', Logger::TYPE_WARN);
        }

        $this->isTransactionOpened = true;
        $this->connect()->_openTransaction();
        $this->off(self::EVENT_COMPLETE);
        return $this;
    }

    abstract protected function _commitTransaction();

    public function commitTransaction()
    {
        $this->log(__FUNCTION__);
        $this->isTransactionOpened = false;
        $this->connect()->_commitTransaction();
        $this->trigger(self::EVENT_COMPLETE);
        return $this;
    }

    abstract protected function _rollBackTransaction();

    public function rollBackTransaction()
    {
        $this->log(__FUNCTION__);
        $this->isTransactionOpened = false;
        $this->connect()->_rollBackTransaction();
        return $this;
    }

    public function makeTransaction(\Closure $fnOk, $default = null)
    {
        $this->openTransaction();

        try {
            $output = $fnOk($this);
            $this->commitTransaction();
        } catch (\Exception $ex) {
            $this->logException($ex);
            $this->rollBackTransaction();

//            if (null === $default) {
//                throw $ex;
//            }

            $output = $default;
        }

        return $output;
    }

    abstract protected function getMaxPerQueryPlaceholdersCount();

    abstract public function makeQuery($rawQuery, bool $isLikeInsteadMatch = false);

    abstract public function showCreateTable(string $table, bool $ifNotExists = true, bool $autoIncrement = false);

    abstract public function createTable(string $table, array $records, string $engine = 'InnoDB', string $charset = 'utf8', bool $temporary = false);

    abstract public function createLikeTable(string $table, string $newTable);

    abstract public function addTableColumn(string $table, string $column, $options);

    abstract public function dropTableColumn(string $table, string $column);

    abstract protected function _addTableKey(string $table, string $key, array $columns, bool $unique = false);

    public function normalizeKey(string $key, $unique = false)
    {
        $prefix = $unique ? 'uk_' : 'ix_';
        $key = $prefix . preg_replace('/^' . $prefix . '/', '', $key);
        return $key;
    }

    public function addTableKey(string $table, string $key, $column, $unique = false)
    {
        return $this->_addTableKey($table, $this->normalizeKey($key, $unique), is_array($column) ? $column : [$column], $unique);
    }

    abstract protected function _dropTableKey(string $table, string $key);

    public function dropTableKey(string $table, string $key, bool $unique = false)
    {
        return $this->_dropTableKey($table, $this->normalizeKey($key, $unique));
    }

    abstract public function renameTable(string $table, string $newTable);

    abstract public function truncateTable(string $table);

    abstract public function dropTable(string $table);

    abstract public function fileReq(string $file, App $app);
}