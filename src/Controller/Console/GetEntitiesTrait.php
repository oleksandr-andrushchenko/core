<?php

namespace SNOWGIRL_CORE\Controller\Console;

use ReflectionException;
use SNOWGIRL_CORE\Console\ConsoleApp as App;
use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\Helper\Classes;
use ReflectionClass;

trait GetEntitiesTrait
{
    /**
     * @param App $app
     * @return Entity[]
     * @throws ReflectionException
     */
    protected function getEntities(App $app): array
    {
        $output = [];

        foreach ($app->namespaces as $namespace) {
            foreach (Classes::getInNamespace($app->loader, $namespace . '\\Entity') as $entity) {
                $ref = new ReflectionClass($entity);

                if ($ref->isAbstract() || $ref->isInterface() || !$ref->isSubclassOf(Entity::class)) {
                    continue;
                }

                $output[] = $entity;
            }
        }

        return $output;
    }
}
