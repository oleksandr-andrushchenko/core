<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\Console\ConsoleApp as App;

class ValidateEntitiesAction
{
    use PrepareServicesTrait;
    use GetEntitiesTrait;
    use OutputTrait;

    /**
     * @param App $app
     *
     * @throws \SNOWGIRL_CORE\Http\Exception\NotFoundHttpException
     */
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $tables = $app->container->db->getManager()->getTables();

        foreach ($this->getEntities($app) as $entity) {
            $entityTable = $entity::getTable();

            if (in_array($entityTable, $tables)) {
                $entityColumns = array_keys($entity::getColumns());

                if ($columns = $app->container->db->getManager()->getColumns($entityTable)) {
                    foreach ($entityColumns as $entityColumn) {
                        if (!in_array($entityColumn, $columns)) {
                            $this->output($entityTable . ': no db "' . $entityColumn . '" column', $app);
                        }
                    }
                } else {
                    $this->output($entityTable . ': no db columns', $app);
                }
            } else {
                $this->output($entityTable . ': db missed', $app);
            }
        }

        $app->response->setBody('DONE');
    }
}