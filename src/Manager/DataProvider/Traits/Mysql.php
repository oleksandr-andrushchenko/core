<?php

namespace SNOWGIRL_CORE\Manager\DataProvider\Traits;

use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\Service\Storage\Query\Expr;

trait Mysql
{
    public function getListByQuery(string $query, bool $prefix = false): array
    {
        $query = trim($query);
        $query = addslashes($query);
        $columns = $this->manager->findColumns(Entity::SEARCH_IN);

        $db = $this->manager->getApp()->storage->mysql(null, $this->manager->getMasterServices());
        $this->manager->setStorageObject($db);

        $this->manager->setColumns(['*', new Expr(implode(' + ', array_map(function ($column) use ($db) {
                return 'CHAR_LENGTH(' . $db->quote($column) . ')';
            }, $columns)) . ' AS ' . $db->quote('len'))]);

        if ($query) {
            $likeQuery = implode(' OR ', array_map(function ($column) use ($db) {
                return $db->quote($column) . ' LIKE ?';
            }, $columns));

            $whereQueryParams = [];
            $whereQueryParams[] = $likeQuery;
            $whereQueryParams = array_merge($whereQueryParams, array_fill(0, count($columns), ($prefix ? '' : '%') . $query . '%'));

            $this->manager->addWhere(new Expr(...$whereQueryParams));

            $orderQueryParams = [];
            $orderQueryParams[] = 'CASE WHEN ' . $likeQuery . ' THEN 1 ELSE 2 END';
            $orderQueryParams = array_merge($orderQueryParams, array_fill(0, count($columns), $query . '%'));

            $this->manager->addOrder(new Expr(...$orderQueryParams))
                ->addOrder(['len' => SORT_ASC]);
        }

        return $this->manager->getList();
    }

    public function getCountByQuery(string $query, bool $prefix = false): int
    {
        $query = trim($query);
        $query = addslashes($query);
        $columns = $this->manager->findColumns(Entity::SEARCH_IN);

        $db = $this->manager->getApp()->storage->mysql(null, $this->manager->getMasterServices());
        $this->manager->setStorageObject($db);

        if ($query) {
            $likeQuery = implode(' OR ', array_map(function ($column) use ($db) {
                return $db->quote($column) . ' LIKE ?';
            }, $columns));

            $whereQueryParams = [];
            $whereQueryParams[] = $likeQuery;
            $whereQueryParams = array_merge($whereQueryParams, array_fill(0, count($columns), ($prefix ? '' : '%') . $query . '%'));

            $this->manager->addWhere(new Expr(...$whereQueryParams));
        }

        return $this->manager->getCount();
    }
}