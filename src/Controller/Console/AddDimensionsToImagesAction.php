<?php

namespace SNOWGIRL_CORE\Controller\Console;

use Imagick;
use ReflectionException;
use SNOWGIRL_CORE\Console\ConsoleApp as App;
use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\Helper\WalkChunk;
use SNOWGIRL_CORE\Images;
use SNOWGIRL_CORE\Mysql\MysqlQuery;
use SNOWGIRL_CORE\Mysql\MysqlQueryExpression;
use Throwable;

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

//        (new DeleteOldImagesAction)($app);

        $tableToColumns = $this->getTableToColumns($app);
        $this->modifyColumns($tableToColumns, $app);

        $mysql = $app->container->mysql;
        $images = $app->images;

        $affFiles = 0;
        $affRecords = 0;

        $query = new MysqlQuery();
        $query->log = false;

        foreach ($tableToColumns as $table => $columns) {
            $manager = $app->managers->getByTable($table);
            $itemPk = $manager->getEntity()->getPk();
            $itemTable = $manager->getEntity()->getTable();

            $compositePk = is_array($itemPk);

            foreach ($columns as $column) {
                if ($compositePk) {
                    $query->text = implode(' ', [
                        'UPDATE ' . $mysql->quote($itemTable),
                        'SET ' . $mysql->quote($column) . ' = ?',
                        'WHERE ' . implode(' AND ', array_map(function ($itemPk) use ($mysql) {
                            return $mysql->quote($itemPk) . ' = ?';
                        }, $itemPk)),
                    ]);
                } else {
                    $query->text = implode(' ', [
                        'UPDATE ' . $mysql->quote($itemTable),
                        'SET ' . $mysql->quote($column) . ' = ?',
                        'WHERE ' . $mysql->quote($itemPk) . ' = ?',
                    ]);
                }

                $batch = 0;
                $postfixesCache = [];

                (new WalkChunk(1000))
                    ->setFnGet(function ($page, $size) use ($app, $manager, $mysql, $itemPk, $column, $compositePk) {
                        return $manager
                            ->setColumns(array_merge($compositePk ? $itemPk : [$itemPk], [$column]))
                            ->setWhere(new MysqlQueryExpression('LENGTH(' . $mysql->quote($column) . ') = ' . $app->images->getHashLength()))
                            ->setOrders([$itemPk => SORT_ASC])
//                            ->setOffset(($page - 1) * $size)
                            ->setLimit($size)
                            ->getArrays();
                    })
                    ->setFnDo(function (array $items) use ($app, $images, $mysql, $itemPk, $column, $query, $compositePk, &$postfixesCache, &$batch, &$affFiles, &$affRecords) {
                        $affTmp = 0;

                        foreach ($items as $item) {
                            $itemHash = $item[$column];

                            if (!$itemHash) {
                                $this->output('Skipped by empty hash', $app);
                                continue;
                            }

                            $image = $images->get($itemHash);

                            if ($image->hasDimensions()) {
                                $this->output('Skipped by existing dimensions', $app);
                                continue;
                            }

                            $postfix = null;

                            $file = $image->getPathname();

                            $this->output($file . ' processing...', $app);

                            if (is_file($file)) {
                                $this->output($file . ' exists...', $app);

                                try {
                                    if (true) {
                                        $imagick = new Imagick($file);
                                        $width = $imagick->getImageWidth();
                                        $height = $imagick->getImageHeight();
                                        $imagick->destroy();
                                    } else {
                                        if (!$info = getimagesize($file)) {
                                            $this->output('Skipped by wrong getimagesize result', $app);
                                            continue;
                                        }

                                        $width = $info[0];
                                        $height = $info[1];
                                    }
                                } catch (Throwable $e) {
                                    $this->output('Skipped by exception: ' . $e->getMessage(), $app);
                                    continue;
                                }

                                if (!$width || !$height) {
                                    $this->output('Skipped by wrong dimensions', $app);
                                    continue;
                                }

                                $postfix = '_' . $width . 'x' . $height;
                            } else {
                                $this->output($file . ' not exists...', $app);

                                foreach ($postfixesCache as $cachedPostfix) {
                                    if (is_file($app->images->getPathName(Images::FORMAT_NONE, 0, $itemHash . $cachedPostfix))) {
                                        $postfix = $cachedPostfix;

                                        $this->output('Postfix "' . $postfix . '" found[cache] for ' . $file, $app);
                                    }
                                }

                                if (!$postfix) {
                                    foreach (glob($app->images->getPathName(Images::FORMAT_NONE, 0, $itemHash . '_*')) as $pathname) {
                                        if (preg_match('#([a-z0-9]{' . $app->images->getHashLength() . '})(_[1-9][0-9]{0,3}x[1-9][0-9]{0,3})?#', basename($pathname), $matches)) {
                                            $postfix = $matches[2];

                                            if (!in_array($postfix, $postfixesCache)) {
                                                $postfixesCache[] = $postfix;
                                            }

                                            $this->output('Postfix "' . $postfix . '" found[lookup] for ' . $file, $app);
                                        }
                                    }
                                }
                            }

                            if (!$postfix) {
                                $this->output('Skipped by empty postfix', $app);
                            }

                            $newItemHash = $itemHash . $postfix;

                            $images->walkLocal('*', '*', $itemHash, function (array $files) use ($app, $postfix, &$affFiles) {
                                foreach ($files as $file) {
                                    if (!$pos = strrpos($file, '.')) {
                                        $this->output('FAILED: dot not found in ' . $file, $app);
                                        continue;
                                    }

                                    $newFile = substr($file, 0, $pos) . $postfix . substr($file, $pos);

                                    if (!rename($file, $newFile)) {
                                        $this->output('FAILED: ' . $file . ' renamed into ' . $newFile, $app);
                                        continue;
                                    }

                                    $this->output('OK: ' . $file . ' renamed into ' . $newFile, $app);

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

                            if ($mysql->req($query)) {
                                $this->output('OK: ' . $itemHash . ' renamed into ' . $newItemHash, $app);
                                $affTmp++;
                                $affRecords++;
                            }
                        }

                        $this->output('#' . $batch . ' Updated ' . $affTmp . ' images', $app);
                        $batch++;
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
        $mysql = $app->container->mysql;

        foreach ($tableToColumns as $table => $columns) {
            $mysql->req(implode(' ', [
                'ALTER TABLE' . ' ' . $mysql->quote($table),
                implode(', ', array_map(function ($column) use ($app, $mysql, $table) {
                    $showCreateColumn = $mysql->showCreateColumn($table, $column);
                    $quotedColumn = $mysql->quote($column);

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