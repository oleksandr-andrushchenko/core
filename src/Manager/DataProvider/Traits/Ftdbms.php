<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 4/18/19
 * Time: 2:38 PM
 */

namespace SNOWGIRL_CORE\Manager\DataProvider\Traits;

use SNOWGIRL_CORE\Manager;
use SNOWGIRL_CORE\Service\Storage\Query\Expr;

trait Ftdbms
{
    public function getListByQuery(string $query, bool $prefix = false): array
    {
        $src = $this->manager->copy(true)
            ->setStorage(Manager::STORAGE_FTDBMS)
            ->addColumn('id')
            ->addWhere(new Expr('MATCH(?)', ($prefix ? '' : '*') . $query . '*'));

        if (strlen($query) <= 2) {
            $src->setLimit(1000);
        }

        $ids = $src->getList('id');

        if (is_array($ids)) {
            if ($ids) {
                return $this->manager->setStorage(Manager::STORAGE_RDBMS)
                    ->addWhere([$this->manager->getEntity()->getPk() => $ids])
                    ->getList();
            }

            return [];
        }

        return [];
    }
}