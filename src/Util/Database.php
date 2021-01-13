<?php

namespace SNOWGIRL_CORE\Util;

use Monolog\Logger;
use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\File;
use SNOWGIRL_CORE\Helper\WalkChunk;
use SNOWGIRL_CORE\Mysql\MysqlQueryExpression;
use SNOWGIRL_CORE\Mysql\MysqlQuery;
use SNOWGIRL_CORE\Util;
use Throwable;

class Database extends Util
{
    public function doFixTablesUpdatedAtColumn()
    {
        $mysql = $this->app->container->mysql;

        foreach ($mysql->getTables() as $table) {
            try {
                $mysql->req(implode(' ', [
                    'ALTER TABLE' . ' ' . $mysql->quote($table) . ' CHANGE ' . $mysql->quote('updated_at'),
                    $mysql->quote('updated_at') . ' timestamp NULL ON UPDATE CURRENT_TIMESTAMP'
                ]));
            } catch (Throwable $e) {

            }
        }

        return true;
    }

    public function doCreateTableDump($table, $where = '', $target = '')
    {
        $create = $this->app->container->mysql->showCreateTable($table, true);
        $insert = [];

        $manager = $this->app->managers->getByTable($table)->clear();

        if ($where = trim($where)) {
            $manager->setWhere(new MysqlQueryExpression($where));
        }

        $manager->setOrders([$manager->getEntity()->getPk() => SORT_ASC]);

        $columns = $manager->getEntity()->getColumns();

        (new WalkChunk(1000))
            ->setFnGet(function ($page, $size) use ($manager, $where) {
                return $manager->setOffset(($page - 1) * $size)
                    ->setLimit($size)
                    ->getArrays();
            })
            ->setFnDo(function ($items) use ($columns, &$insert) {
                foreach ($items as $item) {
                    $temp = [];

                    foreach ($item as $k => $v) {
                        $v = addslashes($v);

                        if (isset($columns[$k]) && isset($columns[$k]['type']) && in_array($columns[$k]['type'], [Entity::COLUMN_INT, Entity::COLUMN_FLOAT])) {
                            $temp[] = $v;
                        } else {
                            $temp[] = '\'' . $v . '\'';
                        }
                    }

                    $insert[] = '(' . implode(',', $temp) . ')';
                }
            })
            ->run();

        if ($insert) {
            $insert = 'INSERT ' . 'INTO ' . $this->app->container->mysql->quote($table) . ' VALUES ' . implode(',', $insert);
        }


        if ($create || $insert) {
            if (!$target = trim($target)) {
                $target = $this->app->config('app.tmp') . '/dump_' . date('Y-m-d') . '_' . $table . '_' . md5($where) . '.sql';
            }

            $file = new File($target);

            $file->clear();
            $file->write('/** Table: "' . $table . '" */');
            $file->writeNewLine();
            $file->write('/** Where: "' . $where . '" */');
            $file->writeNewLine();
            $file->write('/** Date: "' . date('y-m-d') . '" */');

            if ($create) {
                $file->writeNewLine();
                $file->writeNewLine();
                $file->write($create . ';');
            }

            if ($insert) {
                $file->writeNewLine();
                $file->writeNewLine();
                $file->write($insert . ';');
            }

            $file->close();

            $this->output('DONE');
            return true;
        }

        $this->output('FAILED');
        return false;
    }

    public function doMigrateDataFromTableToTable($tableFrom, $tableTo, array $columns = null, $where = null)
    {
        $mysql = $this->app->container->mysql;

        $count = $mysql->selectCount($tableFrom, new MysqlQuery(['params' => [], 'where' => $where]));

        $columnsFrom = [];
        $columnsTo = [];

        if ($columns) {
            foreach ($columns as $columnFrom => $columnTo) {
                $columnsFrom[] = is_string($columnFrom) ? $columnFrom : $columnTo;
                $columnsTo[] = $columnTo;
            }
        }

        $quote = function ($column) use ($mysql) {
            return $mysql->quote($column);
        };


        $req = new MysqlQuery(['params' => []]);
        $req->text = implode(' ', [
            'INSERT INTO',
            $mysql->quote($tableTo),
            $columnsFrom ? ('(' . implode(', ', array_map($quote, $columnsFrom)) . ')') : '',
            'SELECT',
            $columnsTo ? implode(', ', array_map($quote, $columnsTo)) : '*',
            'FROM',
            $mysql->quote($tableFrom),
            $where ? $mysql->makeWhereSQL($where, $req->params, null, $req->placeholders) : ''
        ]);

        $mysql->req($req);

        $count2 = $mysql->selectCount($tableTo, new MysqlQuery(['params' => [], 'where' => $where]));

        if ($count != $count2) {
            $this->output('source and target tables counts mismatched', Logger::ERROR);
            return false;
        }

        return true;
    }
}