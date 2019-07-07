<?php

namespace SNOWGIRL_CORE\Service\Rdbms;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\Exception\Storage as Exception;
use SNOWGIRL_CORE\Helper\Arrays;
use SNOWGIRL_CORE\Service\Storage\Query\Expr;
use SNOWGIRL_CORE\Service\Rdbms;
use SNOWGIRL_CORE\Service\Storage\Query;

/**
 * Class Mysql
 *
 * @package SNOWGIRL_CORE\Service\Rdbms
 * @property \mysqli_result req
 * @property \mysqli        client
 */
class Mysql extends Rdbms
{
    protected function _getClient2()
    {
        return (new \mysqli($this->host, $this->user, $this->password, $this->schema, $this->port, $this->socket))
            ->set_charset('utf8');
    }

    protected function _getClient()
    {
        $client = new \mysqli($this->host, $this->user, $this->password, $this->schema, $this->port, $this->socket);

//        if ($client->connect_error) {
//            throw new Exception($client->connect_error, $client->connect_errno);
//        }

        $client->set_charset('utf8');

        return $client;
    }

    protected function dropMySQLi()
    {
        if ($this->client instanceof \mysqli) {
            $this->client->close();
        }

        $this->client = null;
    }

    protected function dropMySQLiResult()
    {
        if ($this->req instanceof \mysqli_result) {
            $this->req->free();
        }

        $this->req = null;
    }

    protected function dropMySQLiSTMT()
    {
        if ($this->stmt instanceof \mysqli_stmt) {
            $this->stmt->close();
        }

        $this->stmt = null;
    }

    protected function _dropClient()
    {
        $this->dropMySQLiResult();
        $this->dropMySQLiSTMT();
        $this->dropMySQLi();
//        parent::_dropClient();
    }

    /** @var \mysqli_stmt */
    protected $stmt;

    /**
     * @param Query $query
     *
     * @return bool|\mysqli_result|Rdbms|\SNOWGIRL_CORE\Service\Storage
     * @throws Exception
     */
    protected function _req(Query $query)
    {
        $this->dropMySQLiResult();
        $this->dropMySQLiSTMT();

        if ($query->params) {
            $this->stmt = $this->client->stmt_init();

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
            $output = $this->client->query($query->text);

            if (false === $output) {
                throw new Exception($this->client->error, $this->client->errno);
            }
        }

        return $output;
    }

    public function foundRows()
    {
        return (int)$this->req('SELECT FOUND_ROWS() AS ' . $this->quote('c'))->reqToArray()['c'];
    }

    public function numRows()
    {
        return $this->req->num_rows;
    }

    /**
     * @return int
     */
    public function affectedRows()
    {
        if ($this->stmt instanceof \mysqli_stmt) {
            return $this->stmt->affected_rows;
        }

        if ($this->client instanceof \mysqli) {
            return $this->client->affected_rows;
        }

        $this->log('can\'t fetch affected_rows');
        return null;
    }

    public function insertedId()
    {
        if ($this->stmt instanceof \mysqli_stmt) {
            return $this->stmt->insert_id;
        }

        if ($this->client instanceof \mysqli) {
            return $this->client->insert_id;
        }

        $this->log('can\'t fetch insert_id');
        return null;
    }

    public function getErrorNumber()
    {
        return substr($this->client->sqlstate, 0, 5);
    }

    public function getErrorInfo()
    {
        return [
            $this->client->errno,
            $this->client->error,
        ];
    }

    public function warnings()
    {
        return array_map(function ($item) {
            return [
                'code' => $item['Code'],
                'message' => $item['Message']
            ];
        }, $this->req('SHOW WARNINGS')->reqToArrays());
    }

    public function errors()
    {
        return array_map(function ($item) {
            return [
                'code' => $item['Code'],
                'message' => $item['Message']
            ];
        }, $this->req('SHOW ERRORS')->reqToArrays());
    }

    /**
     * @param \Exception|null $ex
     *
     * @return array
     */
    public function getErrors(\Exception $ex = null)
    {
        $output = [];

        $glue = ' - ';

        if ($ex) {
            $output[] = implode($glue, [
                $ex->getCode(),
                $ex->getMessage()
            ]);
        }

        foreach ($this->client->error_list as $error) {
            $output[] = implode($glue, [
                $error['errno'],
                $error['sqlstate'],
                $error['error']
            ]);
        }

        if ($this->stmt && $this->stmt->errno && $this->stmt->error) {
            $output[] = implode($glue, [
                $this->stmt->errno,
                $this->stmt->error
            ]);
        }

//        try {
//            foreach ($this->showErrors() as $error) {
//                $output[] = implode($glue, [
//                    $error['code'],
//                    $error['message']
//                ]);
//            }
//        } catch (\Exception $ex) {
//
//        }

        return $output;
    }

    public function reqToArrays($key = null)
    {
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

    public function reqToArray()
    {
        $this->req->data_seek(0);

        while ($row = $this->req->fetch_array(MYSQLI_ASSOC)) {
            return $row;
        }

        return null;
    }

    public function getTables()
    {
        $output = [];

        foreach ($this->req('SHOW TABLES')->reqToArrays() as $table) {
            $output[] = $table['Tables_in_' . $this->schema];
        }

        return $output;
    }

    public function getColumns($table)
    {
        $output = [];

        foreach ($this->req('SHOW COLUMNS FROM ' . $this->quote($table))->reqToArrays() as $row) {
            $output[] = $row['Field'];
        }

        return $output;
    }

    public function fileReq($file, App $app)
    {
        $file = $app->getServerDir($file);
        $hash = md5_file($file);

        $fileCache = $app->dirs['@tmp'] . '/' . $hash;

        if (file_exists($fileCache)) {
            return true;
        }

        file_put_contents($fileCache, file_get_contents($file));

        return shell_exec(implode(' ', [
            'mysql',
            '-h ' . $this->getHost(),
            '-u ' . $this->getUser(),
            '-p ' . $this->getPassword(),
            $this->getSchema(),
            '< ' . $file
        ]));
    }

    public function reqToObjects($className, $key = null)
    {
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

    public function _quote($v, $table = '')
    {
        if ($table) {
            $table = "`$table`.";
        }

        if ('*' != $v) {
            $v = "`$v`";
        }

        return $table . $v;
    }

    protected function _openTransaction()
    {
        $this->client->autocommit(false);
    }

    protected function _commitTransaction()
    {
        $this->client->commit();
        $this->client->autocommit(true);
    }

    protected function _rollBackTransaction()
    {
        $this->client->rollback();
        $this->client->autocommit(true);
    }

    public function makeQuery($rawQuery, $isLikeInsteadMatch = false)
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

    protected function getMaxPerQueryPlaceholdersCount()
    {
        return 65535;
    }

    public function selectFromEachGroup($table, $group, int $perGroup, Query $query = null)
    {
        $query = Query::normalize($query);

        $query->columns = Arrays::cast($query->columns);
        $query->columns[] = $group;

        $query->text = implode(' ', [
            $this->makeSelectSQL($query->columns, $query->foundRows, $query->params),
            'FROM (',
            $this->makeSelectSQL(array_merge(array_unique(array_merge($query->columns, [$group])), [
                new Expr('@rn := IF(@prev = ' . $this->quote($group) . ', @rn + 1, 1) AS ' . $this->quote('rn')),
                new Expr('@prev := ' . $this->quote($group))
            ]), false, $query->params),
            $this->makeFromSQL($table),
            'JOIN (SELECT @prev := NULL, @rn := 0) AS ' . $this->quote('join'),
            $this->makeWhereSQL($query->where, $query->params),
            $this->makeOrderSQL(array_merge([$group], Arrays::cast($query->orders)), $query->params),
            ') AS ' . $this->quote('dynamic'),
            $this->makeWhereSQL(new Expr($this->quote('rn') . ' <= ' . $perGroup), $query->params)
        ]);

        $tmp = $this->req($query)->reqToArrays();

        $output = [];

        foreach ($tmp as $row) {
            if (!isset($output[$row[$group]])) {
                $output[$row[$group]] = [];
            }

            $output[$row[$group]][] = $row;
        }

        return $output;
    }

    public function showCreateTable($table, $ifNotExists = true, $autoIncrement = false)
    {
        $output = null;

        $createTable = 'CREATE TABLE';

        if ($tmp = $this->req('SHOW ' . $createTable . ' ' . $this->quote($table))->reqToArray()) {
            if (isset($tmp['Create Table'])) {
                $output = $tmp['Create Table'];

                if ($ifNotExists) {
                    $output = str_replace($createTable, $createTable . ' IF NOT EXISTS', $output);
                }

                if (!$autoIncrement) {
                    $output = preg_replace('/ AUTO_INCREMENT=[0-9]+/', '', $output);
                }

//                $output .= ';';
            }
        }

        return $output;
    }

    public function createTable($table, array $records, $engine = 'InnoDB', $charset = 'utf8', $temporary = false)
    {
        return $this->req(implode(' ', [
            'CREATE ' . ($temporary ? 'TEMPORARY ' : '') . 'TABLE IF NOT EXISTS',
            $this->quote($table),
            "(\r\n",
            implode(",\r\n", $records),
            "\r\n)",
            'ENGINE=' . $engine,
            'DEFAULT CHARSET=' . $charset
        ]));
    }

    public function createLikeTable($table, $newTable)
    {
        return $this->req(implode(' ', ['CREATE TABLE', $this->quote($newTable), 'LIKE', $this->quote($table)]));
    }

    public function addTableColumn($table, $column, $options)
    {
        return $this->req(implode(' ', ['ALTER TABLE', $this->quote($table), 'ADD COLUMN', $this->quote($column), $options]));
    }

    public function dropTableColumn($table, $column)
    {
        return $this->req(implode(' ', ['ALTER TABLE', $this->quote($table), 'DROP', 'IF EXISTS', $this->quote($column)]));
    }

    protected function _addTableKey($table, $key, array $columns, $unique = false)
    {
        return $this->req(implode(' ', [
            'ALTER TABLE',
            $this->quote($table),
            'ADD' . ($unique ? ' UNIQUE' : '') . ' KEY',
            $this->quote($key),
            '(' . implode(', ', array_map([$this, 'quote'], $columns)) . ')'
        ]));
    }

    protected function _dropTableKey($table, $key)
    {
        return $this->req(implode(' ', ['ALTER TABLE', $this->quote($table), 'DROP KEY', 'IF EXISTS', $this->quote($key)]));
    }

    public function renameTable($table, $newTable)
    {
        return $this->req(implode(' ', ['RENAME TABLE', $this->quote($table), 'TO', $this->quote($newTable)]));
    }

    public function truncateTable($table)
    {
        return $this->req(implode(' ', ['TRUNCATE TABLE', $this->quote($table)]));
    }

    public function dropTable($table)
    {
        return $this->req(implode(' ', ['DROP TABLE', 'IF EXISTS', $this->quote($table)]));
    }
}