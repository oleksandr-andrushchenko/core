<?php

namespace SNOWGIRL_CORE\URI;

use SNOWGIRL_CORE\AbstractApp;
use SNOWGIRL_CORE\App\ConsoleApp;
use SNOWGIRL_CORE\Http\HttpApp;
use SNOWGIRL_CORE\Entity;

use SNOWGIRL_CORE\Helper\Arrays;
use SNOWGIRL_CORE\Helper\Classes;
use SNOWGIRL_CORE\Query\Expression;
use SNOWGIRL_CORE\Query;

class Manager
{
    /**
     * @var HttpApp|ConsoleApp
     */
    protected $app;

    public function __construct(AbstractApp $app)
    {
        $this->app = $app;
    }

    public function getEntitiesBySlug($slug, $entitiesToCheck = null, $entitiesToExclude = null, $column = 'uri'): array
    {
        $output = [];

        if (null === $entitiesToCheck) {
            $entitiesToCheck = Classes::getInNsCheckAppNs('Entity', $this->app);
        }

        $entitiesToCheck = Arrays::cast($entitiesToCheck);
        $entitiesToExclude = Arrays::cast($entitiesToExclude);

        $entitiesToCheck = array_filter($entitiesToCheck, function ($entity) use ($entitiesToExclude, $column) {
            /** @var Entity $entity */
            return $entity && !in_array($entity, $entitiesToExclude) &&
                is_subclass_of($entity, Entity::class, true) &&
                array_key_exists($column, $entity::getColumns());
        });

        $db = $this->app->container->db;

        $req = new Query(['params' => []]);
        $req->text = implode(' UNION ', array_map(function ($entity) use ($db, $slug, $column, $req) {
            /** @var Entity $entity */

            return implode(' ', [
                $db->makeSelectSQL(new Expression(implode(', ', [
                    '\'' . addslashes($entity::getClass()) . '\' AS ' . $db->quote('class'),
//                    '\'' . $entity::getPk() . '\' AS ' . $db->quote('pk'),
//                    '\'' . $entity::getTable() . '\' AS ' . $db->quote('table'),
                    $db->quote($entity::getPk()) . ' AS ' . $db->quote('id')
                ])), false, $req->params),
                $db->makeFromSQL($entity::getTable()),
                $db->makeWhereSQL([$column => $slug], $req->params)
            ]);
        }, $entitiesToCheck));

        $req = $db->reqToArrays($req);

        foreach ($req as $item) {
            $output[$item['class']] = $item['id'];
        }

        return $output;
    }
}