<?php

namespace SNOWGIRL_CORE\URI;

use SNOWGIRL_CORE\AbstractApp;
use SNOWGIRL_CORE\App\ConsoleApp;
use SNOWGIRL_CORE\Http\HttpApp;
use SNOWGIRL_CORE\Entity;

use SNOWGIRL_CORE\Helper\Arrays;
use SNOWGIRL_CORE\Helper\Classes;
use SNOWGIRL_CORE\Mysql\MysqlQueryExpression;
use SNOWGIRL_CORE\Mysql\MysqlQuery;

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

        $mysql = $this->app->container->mysql;

        $req = new MysqlQuery(['params' => []]);
        $req->text = implode(' UNION ', array_map(function ($entity) use ($mysql, $slug, $column, $req) {
            /** @var Entity $entity */

            return implode(' ', [
                $mysql->makeSelectSQL(new MysqlQueryExpression(implode(', ', [
                    '\'' . addslashes($entity::getClass()) . '\' AS ' . $mysql->quote('class'),
//                    '\'' . $entity::getPk() . '\' AS ' . $mysql->quote('pk'),
//                    '\'' . $entity::getTable() . '\' AS ' . $mysql->quote('table'),
                    $mysql->quote($entity::getPk()) . ' AS ' . $mysql->quote('id')
                ])), false, $req->params),
                $mysql->makeFromSQL($entity::getTable()),
                $mysql->makeWhereSQL([$column => $slug], $req->params, null, $req->placeholders)
            ]);
        }, $entitiesToCheck));

        $req = $mysql->reqToArrays($req);

        foreach ($req as $item) {
            $output[$item['class']] = $item['id'];
        }

        return $output;
    }
}