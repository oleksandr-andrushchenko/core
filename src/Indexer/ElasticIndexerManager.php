<?php

namespace SNOWGIRL_CORE\Indexer;

use Elasticsearch\Common\Exceptions\Missing404Exception;
use Throwable;

class ElasticIndexerManager implements IndexerManagerInterface
{
    /**
     * @var ElasticIndexer
     */
    private $indexer;

    public function __construct(ElasticIndexer $indexer)
    {
        $this->indexer = $indexer;
    }

    public function indexMany(string $name, array $documents): int
    {
        if (!$documents) {
            return 0;
        }

        $input = ['body' => []];

        $index = $this->indexer->makeIndexName($name);

        foreach ($documents as $id => $document) {
            $input['body'][] = [
                'index' => [
                    '_index' => $index,
                    '_id' => $id,
                ],
            ];

            $input['body'][] = $document;
        }

        try {
//        $this->updateIndex($index, ['refresh_interval' => -1, 'number_of_replicas' => 0]);

//        $input['refresh'] = true;

            $output = $this->indexer->getClient()->bulk($input);

//        $this->updateIndex($index, ['refresh_interval' => null, 'number_of_replicas' => 1]);
//        $this->forceMerge($index);

            if (is_array($output) && array_key_exists('items', $output) && is_array($output['items'])) {
                $this->indexer->getLogger()->info('Documents has been indexed', compact('name', 'index'));
            } else {
                $this->indexer->getLogger()->error('Documents has not been indexed', compact('name', 'index', 'documents', 'output'));
                return false;
            }

            return count(array_filter($output['items'], function ($item) {
                return is_array($item) &&
                    array_key_exists('index', $item) &&
                    is_array($item['index']) &&
                    array_key_exists('result', $item['index'])
                    && 'created' == $item['index']['result'];
            }));
        } catch (Missing404Exception $e) {
            $this->indexer->getLogger()->warning('Trying to index documents on non-existing index', compact('name', 'index', 'e'));
            return 0;
        } catch (Throwable $e) {
            $this->indexer->getLogger()->error($e);
            return 0;
        }
    }

    public function deleteIndex(string $name): bool
    {
        $index = $this->indexer->makeIndexName($name);

        try {
            $output = $this->indexer->getClient()->indices()->delete([
                'index' => $index,
            ]);

            $result = is_array($output) && array_key_exists('acknowledged', $output) && true == $output['acknowledged'];

            if ($result) {
                $this->indexer->getLogger()->info('Index has been deleted', compact('name', 'index'));
            } else {
                $this->indexer->getLogger()->error('Index has not been deleted', compact('name', 'index', 'output'));
            }

            return $result;
        } catch (Missing404Exception $e) {
            $this->indexer->getLogger()->warning('Trying to delete non-existing index', compact('name', 'index', 'e'));
            return true;
        } catch (Throwable $e) {
            $this->indexer->getLogger()->error($e);
            return false;
        }
    }
    
    public function switchAliasIndex(string $alias, array $mappings = [], callable $job = null)
    {
        $newAliasIndex = $alias . '_' . time();

        $wildcardIndexes = $this->getIndexes($alias . '_*');
        $oldAliasIndexes = $this->getAliasIndexes($alias);

        $this->deleteIndexes(array_diff($wildcardIndexes, $oldAliasIndexes));

        if (!$this->createIndex($newAliasIndex, $mappings)) {
            return false;
        }

        $output = $job ? call_user_func($job, $newAliasIndex) : true;

        if (!$this->updateAliasIndexes($alias, [$newAliasIndex], $oldAliasIndexes)) {
            $this->deleteIndex($newAliasIndex);
            return false;
        }

        $this->deleteIndexes($oldAliasIndexes);

        return $output;
    }

    public function createIndex(string $name, array $mappings = []): bool
    {
        $body = [];

        if ($mappings) {
            $body['mappings'] = $mappings;
        }

        $index = $this->indexer->makeIndexName($name);

        try {
            $output = $this->indexer->getClient()->indices()->create([
                'index' => $index,
                'body' => $body,
            ]);

            $result = is_array($output) && array_key_exists('acknowledged', $output) && true == $output['acknowledged'];

            if ($result) {
                $this->indexer->getLogger()->info('Index has been created', compact('name', 'index'));
            } else {
                $this->indexer->getLogger()->error('Index has not been created', compact('name', 'index', 'mappings', 'output'));
            }

            return $result;
        } catch (Throwable $e) {
            $this->indexer->getLogger()->error($e);
            return false;
        }
    }

    private function deleteIndexes(array $names): int
    {
        $aff = 0;

        foreach ($names as $name) {
            if ($this->deleteIndex($name)) {
                $aff++;
            }
        }

        return $aff;
    }

    private function getIndexMappings(string $name): array
    {
        $index = $this->indexer->makeIndexName($name);

        return $this->indexer->walkRawSearchResults($this->indexer->getClient()->indices()->getMapping([
            'index' => $index,
        ]), [
            $index,
            'mappings',
        ]);
    }

    private function createLikeIndex(string $index, string $newIndex): bool
    {
        return $this->createIndex($newIndex, $this->getIndexMappings($index));
    }

    private function updateIndex(string $name, array $settings): bool
    {
        $output = $this->indexer->getClient()->indices()->putSettings([
            'index' => $this->indexer->makeIndexName($name),
            'body' => [
                'settings' => $settings,
            ],
        ]);

        return is_array($output) && array_key_exists('acknowledged', $output) && 1 == $output['acknowledged'];
    }

    private function updateAliasIndexes(string $name, array $addIndex = [], array $removeIndex = []): bool
    {
        $alias = $this->indexer->makeIndexName($name);
        $actions = [];

        foreach ($removeIndex as $name) {
            $actions[] = [
                'remove' => [
                    'index' => $this->indexer->makeIndexName($name),
                    'alias' => $alias,
                ],
            ];
        }

        foreach ($addIndex as $name) {
            $actions[] = [
                'add' => [
                    'index' => $this->indexer->makeIndexName($name),
                    'alias' => $alias,
                ],
            ];
        }

        try {
            $output = $this->indexer->getClient()->indices()->updateAliases([
                'body' => [
                    'actions' => $actions,
                ],
            ]);

            $result = is_array($output) && array_key_exists('acknowledged', $output) && true == $output['acknowledged'];

            if ($result) {
                $this->indexer->getLogger()->info('Alias indexes has been updated', compact('name', 'alias'));
            } else {
                $this->indexer->getLogger()->error('Alias indexes has not been updated', compact('name', 'alias', 'actions', 'output'));
            }

            return $result;
        } catch (Missing404Exception $e) {
            $this->indexer->getLogger()->warning('Trying to update non-existing indexes alias', compact('name', 'alias', 'actions', 'e'));
            return false;
        } catch (Throwable $e) {
            $this->indexer->getLogger()->error($e);
            return false;
        }
    }

    private function getIndexes(string $name): array
    {
        $index = $this->indexer->makeIndexName($name);

        try {
            $output = $this->indexer->getClient()->indices()->get([
                'index' => $index,
            ]);

            if (is_array($output)) {
                $this->indexer->getLogger()->info('Indexes has been fetched', compact('name', 'index'));
            } else {
                $this->indexer->getLogger()->error('Indexes has not been fetched', compact('name', 'index', 'output'));
                return [];
            }

            return array_map(function ($index) {
                return $this->indexer->makeName($index);
            }, array_keys($output));
        } catch (Missing404Exception $e) {
            $this->indexer->getLogger()->warning('Trying to fetch non-existing indexes', compact('name', 'index', 'e'));
            return [];
        } catch (Throwable $e) {
            $this->indexer->getLogger()->error($e);
            return [];
        }
    }

    private function getAliasIndexes(string $name): array
    {
        $alias = $this->indexer->makeIndexName($name);

        try {
            $output = $this->indexer->getClient()->indices()->getAliases([
                'name' => $alias,
            ]);

            if (is_array($output)) {
                $this->indexer->getLogger()->info('Alias indexes has been fetched', compact('name', 'alias'));
            } else {
                $this->indexer->getLogger()->error('Alias indexes has not been fetched', compact('name', 'alias', 'output'));
                return [];
            }

            return array_map(function ($index) {
                return $this->indexer->makeName($index);
            }, array_keys($output));
        } catch (Missing404Exception $e) {
            $this->indexer->getLogger()->warning('Trying to fetch non-existing alias indexes', compact('name', 'alias', 'e'));
            return [];
        } catch (Throwable $e) {
            $this->indexer->getLogger()->error($e);
            return [];
        }
    }
}
