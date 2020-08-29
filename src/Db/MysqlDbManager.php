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

    /**
     * Should be synced with self::indexExists()
     * @param string $table
     * @param $column
     * @param string|null $key
     * @param bool $unique
     * @return bool
     * @throws DbException
     */
    public function addTableKey(string $table, $column, string $key = null, bool $unique = false): bool
    {
        if (!is_array($column)) {
            $column = [$column];
        }

        if (null === $key) {
            $key = implode('_', $column);
        }

        $this->db->req(implode(' ', [
            'ALTER TABLE',
            $this->db->quote($table),
            'ADD' . ($unique ? ' UNIQUE' : '') . ' KEY',
            $this->db->quote($this->normalizeKey($key, $unique)),
            '(' . implode(', ', array_map([$this->db, 'quote'], $column)) . ')',
        ]));

        return true;
    }

    public function dropTableKey(string $table, string $key, bool $unique = false): bool
    {
        $this->db->req(implode(' ', [
            'ALTER TABLE', $this->db->quote($table),
            'DROP KEY',
            'IF EXISTS',
            $this->db->quote($this->normalizeKey($key, $unique)),
        ]));

        return true;
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

    public function showCreateTable(string $table, bool $ifNotExists = false, bool $autoIncrement = false): string
    {
        $createTable = 'CREATE TABLE';

        if (!$tmp = $this->db->reqToArray('SHOW ' . $createTable . ' ' . $this->db->quote($table))) {
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
            if (false !== strpos($row, $this->db->quote($column))) {
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
        $this->db->req(implode(' ', [
            'CREATE ' . ($temporary ? 'TEMPORARY ' : '') . 'TABLE IF NOT EXISTS',
            $this->db->quote($table),
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
        $this->db->req(implode(' ', ['CREATE TABLE', $this->db->quote($newTable), 'LIKE', $this->db->quote($table)]));

        return true;
    }

    public function addTableColumn(string $table, string $column, $options): bool
    {
        $this->db->req(implode(' ', ['ALTER TABLE', $this->db->quote($table), 'ADD COLUMN', $this->db->quote($column), $options]));

        return true;
    }

    public function dropTableColumn(string $table, string $column): bool
    {
        $this->db->req(implode(' ', ['ALTER TABLE', $this->db->quote($table), 'DROP', 'IF EXISTS', $this->db->quote($column)]));

        return true;
    }

    public function renameTable(string $table, string $newTable): bool
    {
        $this->db->req(implode(' ', ['RENAME TABLE', $this->db->quote($table), 'TO', $this->db->quote($newTable)]));

        return true;
    }

    public function truncateTable(string $table): bool
    {
        $this->db->req(implode(' ', ['TRUNCATE TABLE', $this->db->quote($table)]));

        return true;
    }

    public function dropTable(string $table): bool
    {
        $this->db->req(implode(' ', ['DROP TABLE', 'IF EXISTS', $this->db->quote($table)]));

        return true;
    }

    private function normalizeKey(string $key, bool $unique = false)
    {
        $prefix = $unique ? 'uk_' : 'ix_';
        $key = $prefix . preg_replace('/^' . $prefix . '/', '', $key);
        return $key;
    }
}