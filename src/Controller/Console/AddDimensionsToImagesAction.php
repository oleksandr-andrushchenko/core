<?php

namespace SNOWGIRL_CORE\Controller\Console;

use ReflectionException;
use SNOWGIRL_CORE\Console\ConsoleApp as App;
use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\Helper\WalkChunk;
use SNOWGIRL_CORE\Query;

class AddDimensionsToImagesAction
{
    use PrepareServicesTrait;
    use GetEntitiesTrait;
    use OutputTrait;

    /**
     * @param App $app
     * @throws ReflectionException
     * @throws \SNOWGIRL_CORE\Http\Exception\NotFoundHttpException
     */
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        (new DeleteOldImagesAction)($app);

        $tableToColumns = $this->getTableToColumns($app);
        $this->modifyColumns($tableToColumns, $app);

        $db = $app->container->db;
        $images = $app->images;

        $affFiles = 0;
        $affRecords = 0;

        $query = new Query();
        $query->log = false;

        foreach ($tableToColumns as $table => $columns) {
            $manager = $app->managers->getByTable($table);
            $itemPk = $manager->getEntity()->getPk();
            $itemTable = $manager->getEntity()->getTable();

            $compositePk = is_array($itemPk);

            foreach ($columns as $column) {
                if ($compositePk) {
                    $query->text = implode(' ', [
                        'UPDATE ' . $db->quote($itemTable),
                        'SET ' . $db->quote($column) . ' = ?',
                        'WHERE ' . implode(' AND ', array_map(function ($itemPk) use ($db) {
                            return $db->quote($itemPk) . ' = ?';
                        }, $itemPk)),
                    ]);
                } else {
                    $query->text = implode(' ', [
                        'UPDATE ' . $db->quote($itemTable),
                        'SET ' . $db->quote($column) . ' = ?',
                        'WHERE ' . $db->quote($itemPk) . ' = ?',
                    ]);
                }

                (new WalkChunk(1000))
                    ->setFnGet(function ($page, $size) use ($manager, $db, $itemPk, $column, $compositePk) {
                        return $manager
                            ->setColumns(array_merge($compositePk ? $itemPk : [$itemPk], [$column]))
                            ->setOrders([$itemPk => SORT_ASC])
                            ->setOffset(($page - 1) * $size)
                            ->setLimit($size)
                            ->getArrays();
                    })
                    ->setFnDo(function (array $items) use ($images, $db, $itemPk, $itemTable, $column, $query, $compositePk, &$affFiles, &$affRecords) {
                        foreach ($items as $item) {
                            $itemHash = $item[$column];

                            if (!$itemHash) {
                                continue;
                            }

                            $image = $images->get($itemHash);

                            if ($image->hasDimensions()) {
                                continue;
                            }

                            $file = $image->getPathname();

                            if (!$info = getimagesize($file)) {
                                continue;
                            }

                            $width = $info[0];
                            $height = $info[1];

                            $newItemHash = $itemHash . '_' . $width . 'x' . $height;

                            $images->walkLocal('*', '*', $itemHash, function (array $files) use ($width, $height, &$affFiles) {
                                foreach ($files as $file) {
                                    if (!$pos = strrpos($file, '.')) {
                                        continue;
                                    }

                                    $newFile = substr($file, 0, $pos) . '_' . $width . 'x' . $height . substr($file, $pos);

                                    if (!rename($file, $newFile)) {
                                        continue;
                                    }

                                    $affFiles++;
                                }
                            });

                            if ($compositePk) {
                                $query->params = array_merge([$newItemHash], array_map(function ($itemPk) use ($item) {
                                    return $item[$itemPk];
                                }, $itemPk));
                            } else {
                                $query->params = [$newItemHash, $item[$itemPk]];
                            }

                            if ($db->req($query)) {
                                $affRecords++;
                            }
                        }

                        return end($items) ? key($items) : false;
                    })
                    ->run();
            }
        }

        $app->response->addToBody(implode("\r\n", [
            '',
            __CLASS__,
            "DONE: affFiles={$affFiles} affRecords={$affRecords}",
        ]));
    }

    /**
     * @param App $app
     * @return array
     * @throws ReflectionException
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

    private function modifyColumns(array $tableToColumns, App $app)
    {
        $db = $app->container->db;
        $dbManager = $db->getManager();

        foreach ($tableToColumns as $table => $columns) {
            $db->req(implode(' ', [
                'ALTER TABLE' . ' ' . $db->quote($table),
                implode(', ', array_map(function ($column) use ($app, $db, $dbManager, $table) {
                    $showCreateColumn = $dbManager->showCreateColumn($table, $column);
                    $quotedColumn = $db->quote($column);

                    return 'CHANGE ' . $quotedColumn . ' ' . preg_replace_callback("/[0-9]+/", function ($matches) use ($app) {
//                            return 4 + 1 + 4 + $matches[0];

                            if (40 < $matches[0]) {
                                return $matches[0];
                            }

                            return 1 + 4 + 1 + 4 + $app->images->getHashLength();
                        }, $showCreateColumn);
                }, $columns))
            ]));
        }
    }
}