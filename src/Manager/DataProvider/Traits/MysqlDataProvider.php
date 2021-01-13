<?php

namespace SNOWGIRL_CORE\Manager\DataProvider\Traits;

use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\Manager;
use SNOWGIRL_CORE\Mysql\MysqlQueryExpression;

/**
 * @property Manager $manager
 */
trait MysqlDataProvider
{
    public function getListByQuery(string $term, bool $prefix = false): ?array
    {
        $term = trim($term);
        $term = addslashes($term);
        $columns = $this->manager->findColumns(Entity::SEARCH_IN);

        $mysql = $this->manager->getApp()->container->mysql($this->manager->getMasterServices());
        $this->manager->setMysql($mysql);

        $this->manager->setColumns(['*', new MysqlQueryExpression(implode(' + ', array_map(function ($column) use ($mysql) {
                return 'CHAR_LENGTH(' . $mysql->quote($column) . ')';
            }, $columns)) . ' AS ' . $mysql->quote('len'))]);

        if ($term) {
            $likeQuery = implode(' OR ', array_map(function ($column) use ($mysql) {
                return $mysql->quote($column) . ' LIKE ?';
            }, $columns));

            $whereQueryParams = [];
            $whereQueryParams[] = $likeQuery;
            $whereQueryParams = array_merge($whereQueryParams, array_fill(0, count($columns), ($prefix ? '' : '%') . $term . '%'));

            $this->manager->addWhere(new MysqlQueryExpression(...$whereQueryParams));

            $orderQueryParams = [];
            $orderQueryParams[] = 'CASE WHEN ' . $likeQuery . ' THEN 1 ELSE 2 END';
            $orderQueryParams = array_merge($orderQueryParams, array_fill(0, count($columns), $term . '%'));

            $this->manager->addOrder(new MysqlQueryExpression(...$orderQueryParams))
                ->addOrder(['len' => SORT_ASC]);
        }

        return $this->manager->getList();
    }

    public function getCountByQuery(string $term, bool $prefix = false): ?int
    {
        $term = trim($term);
        $term = addslashes($term);
        $columns = $this->manager->findColumns(Entity::SEARCH_IN);

        $mysql = $this->manager->getApp()->container->mysql($this->manager->getMasterServices());
        $this->manager->setMysql($mysql);

        if ($term) {
            $likeQuery = implode(' OR ', array_map(function ($column) use ($mysql) {
                return $mysql->quote($column) . ' LIKE ?';
            }, $columns));

            $whereQueryParams = [];
            $whereQueryParams[] = $likeQuery;
            $whereQueryParams = array_merge($whereQueryParams, array_fill(0, count($columns), ($prefix ? '' : '%') . $term . '%'));

            $this->manager->addWhere(new MysqlQueryExpression(...$whereQueryParams));
        }

        return $this->manager->getCount();
    }
}