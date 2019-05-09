<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 4/4/19
 * Time: 11:20 PM
 */

namespace SNOWGIRL_CORE\Manager\DataProvider\Traits;

use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\Service\Storage\Query\Expr;

/**
 * Trait Rdbms
 *
 * @package SNOWGIRL_CORE\Manager\DataProvider\Traits
 */
trait Rdbms
{
    public function getListByQuery(string $query, bool $prefix = false): array
    {
        $query = trim($query);
        $query = addslashes($query);
        $columns = $this->manager->findColumns(Entity::SEARCH_IN);

        /** @var \SNOWGIRL_CORE\Service\Rdbms $db */
        $db = $this->manager->getStorageObject();

        $this->manager->setColumns(['*', new Expr(implode(' + ', array_map(function ($column) use ($db) {
                return 'CHAR_LENGTH(' . $db->quote($column) . ')';
            }, $columns)) . ' AS ' . $db->quote('len'))]);

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

        $this->manager->addOrder(new Expr(...$orderQueryParams));

        return $this->manager->addOrder(['len' => SORT_ASC])
//                    ->addOrder(['count' => SORT_DESC])
            ->getList();
    }
}