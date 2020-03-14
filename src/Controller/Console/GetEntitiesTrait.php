<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\Console\ConsoleApp as App;
use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\Helper\Classes;
use ReflectionClass;

trait GetEntitiesTrait
{
    /**
     * @param App $app
     *
     * @return Entity[]
     */
    protected function getEntities(App $app): array
    {
        $output = [];

        foreach ($app->namespaces as $namespace) {
            foreach (Classes::getInNamespace($app->loader, $namespace . '\\Entity') as $entity) {
                $ref = new ReflectionClass($entity);

                if (!$ref->isAbstract()) {
                    $output[] = $entity;
                }
            }
        }

        return $output;
    }
}
