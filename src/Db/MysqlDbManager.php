<?php

namespace SNOWGIRL_CORE\Db;

class MysqlDbManager implements DbManagerInterface
{
    /**
     * @var MysqlDb
     */
    private $db;

    public function __construct(MysqlDb $db)
    {
        $this->db = $db;
    }

    public function addTableKey(string $table, string $key, $column, $unique = false): bool
    {
        return $this->db->req(implode(' ', [
            'ALTER TABLE',
            $this->db->quote($table),
            'ADD' . ($unique ? ' UNIQUE' : '') . ' KEY',
            $this->db->quote($this->normalizeKey($key, $unique)),
            '(' . implode(', ', array_map([$this, 'quote'], is_array($column) ? $column : [$column])) . ')'
        ]));
    }

    public function dropTableKey(string $table, string $key, bool $unique = false): bool
    {
        return $this->db->req(implode(' ', [
            'ALTER TABLE', $this->db->quote($table),
            'DROP KEY',
            'IF EXISTS',
            $this->db->quote($this->normalizeKey($key, $unique))
        ]));
    }

    public function getTables(): array
    {
        $output = [];

        foreach ($this->db->reqToArrays('SHOW TABLES') as $table) {
            $output[] = $table['Tables_in_' . $this->db->getSchema()];
        }

        return $output;
    }

    public function getColumns(string $table): array
    {
        $output = [];

        foreach ($this->db->reqToArrays('SHOW COLUMNS FROM ' . $this->db->quote($table)) as $row) {
            $output[] = $row['Field'];
        }

        return $output;
    }

    public function showCreateTable(string $table, bool $ifNotExists = true, bool $autoIncrement = false)
    {
        $output = null;

        $createTable = 'CREATE TABLE';

        if ($tmp = $this->db->reqToArray('SHOW ' . $createTable . ' ' . $this->db->quote($table))) {
            if (isset($tmp['Create Table'])) {
                $output = $tmp['Create Table'];

                if ($ifNotExists) {
                    $output = str_replace($createTable, $createTable . ' IF NOT EXISTS', $output);
                }

                if (!$autoIncrement) {
                    $output = preg_replace('/ AUTO_INCREMENT=[0-9]+/', '', $output);
                }
            }
        }

        return $output;
    }

    public function getIndexes(string $table): array
    {
        $output = [];

        foreach ($this->db->reqToArrays('SHOW INDEX FROM ' . $this->db->quote($table)) as $tmp) {
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
        return $this->db->req(implode(' ', [
            'CREATE ' . ($temporary ? 'TEMPORARY ' : '') . 'TABLE IF NOT EXISTS',
            $this->db->quote($table),
            "(\r\n",
            implode(",\r\n", $records),
            "\r\n)",
            'ENGINE=' . $engine,
            'DEFAULT CHARSET=' . $charset
        ]));
    }

    public function createLikeTable(string $table, string $newTable): bool
    {
        return $this->db->req(implode(' ', ['CREATE TABLE', $this->db->quote($newTable), 'LIKE', $this->db->quote($table)]));
    }

    public function addTableColumn(string $table, string $column, $options): bool
    {
        return $this->db->req(implode(' ', ['ALTER TABLE', $this->db->quote($table), 'ADD COLUMN', $this->db->quote($column), $options]));
    }

    public function dropTableColumn(string $table, string $column): bool
    {
        return $this->db->req(implode(' ', ['ALTER TABLE', $this->db->quote($table), 'DROP', 'IF EXISTS', $this->db->quote($column)]));
    }

    public function renameTable(string $table, string $newTable): bool
    {
        return $this->db->req(implode(' ', ['RENAME TABLE', $this->db->quote($table), 'TO', $this->db->quote($newTable)]));
    }

    public function truncateTable(string $table): bool
    {
        return $this->db->req(implode(' ', ['TRUNCATE TABLE', $this->db->quote($table)]));
    }

    public function dropTable(string $table): bool
    {
        return $this->db->req(implode(' ', ['DROP TABLE', 'IF EXISTS', $this->db->quote($table)]));
    }

    private function normalizeKey(string $key, $unique = false)
    {
        $prefix = $unique ? 'uk_' : 'ix_';
        $key = $prefix . preg_replace('/^' . $prefix . '/', '', $key);
        return $key;
    }
}