<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\Console\ConsoleApp as App;
use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\Manager;

class DeleteOldImagesAction
{
    use PrepareServicesTrait;
    use GetEntitiesTrait;
    use OutputTrait;

    /**
     * @param App $app
     * @throws \ReflectionException
     * @throws \SNOWGIRL_CORE\Http\Exception\NotFoundHttpException
     */
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $aff = 0;

        $tableToColumns = $this->getTableToColumns($app);
        $tableToIndexes = $this->addIndexes($tableToColumns, $app);

        $managers = $this->getManagersByTableToColumns($tableToColumns, $app);

        $app->images->walkLocal('*', '*', '*', function (array $files, int $batch) use ($app, $tableToColumns, $managers, &$aff) {
            $images = $this->getImagesByFiles($files, $app);

            foreach ($tableToColumns as $table => $columns) {
                /** @var Manager $manager */
                $manager = $managers[$table];

                foreach ($columns as $column) {
                    $images = array_diff($images, $manager->setWhere([$column => $images])->getColumn($column));
                }
            }

            $aff += $this->deleteImages($images, $batch, $app);
        });

        $this->dropIndexes($tableToIndexes, $app);

        $app->response->addToBody(implode("\r\n", [
            '',
            __CLASS__,
            "DONE: {$aff}",
        ]));
    }

    /**
     * @param App $app
     * @return array
     * @throws \ReflectionException
     */
    private function getTableToColumns(App $app): array
    {
        $output = [];

        foreach ($this->getEntities($app) as $entity) {
            $output[$entity::getTable()] = [];

            foreach ($entity::getColumns() as $column => $options) {
                if (in_array(Entity::IMAGE, $options)) {
                    $output[$entity::getTable()][] = $column;
                }
            }
        }

        $output = array_filter($output, function ($columns) {
            return 0 < count($columns);
        });

        return $output;
    }

    private function getManagersByTableToColumns(array $tableToColumns, App $app): array
    {
        $tables = array_keys($tableToColumns);

        $output = array_map(function ($table) use ($app) {
            return $app->managers->getByTable($table);
        }, $tables);

        $output = array_combine($tables, $output);

        return $output;
    }

    private function getImagesByFiles(array $files, App $app): array
    {
        $output = [];

        foreach ($files as $file) {
            $output[] = $app->images->getLocalByFile($file);
        }

        return $output;
    }

    private function deleteImages(array $images, $i, App $app): int
    {
        $aff = 0;

        if ($images) {
            $this->output('#' . $i . ' Deleting ' . count($images) . ' images...', $app);

            foreach ($images as $image) {
                $aff += $app->images->deleteLocal($image);
            }
        } else {
            $this->output('#' . $i . ' No images to delete', $app);
        }

        return $aff;
    }

    private function addIndexes(array $tableToColumns, App $app): array
    {
        $output = [];

        $dbManager = $app->container->db->getManager();

        foreach ($tableToColumns as $table => $columns) {
            $output[$table] = [];

            $indexes = $dbManager->getIndexes($table);

            foreach ($columns as $column) {
                foreach ($indexes as $indexColumns) {
                    if ($indexColumns[0] == $column) {
                        continue 2;
                    }
                }

                $index = 'tmp_' . $column . '_' . time();
                $output[$table][] = $index;

                $this->output('Adding `' . $index . '` index to `' . $table . '` table...', $app);
                $dbManager->addTableKey($table, $index, $column);
            }
        }

        return $output;
    }

    private function dropIndexes(array $tableToIndexes, App $app)
    {
        $dbManager = $app->container->db->getManager();

        foreach ($tableToIndexes as $table => $indexes) {
            foreach ($indexes as $index) {
                $this->output('Dropping `' . $index . '` index to `' . $table . '` table...', $app);
                $dbManager->dropTableKey($table, $index);
            }
        }
    }
}