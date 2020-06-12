<?php

namespace SNOWGIRL_CORE\Indexer;

use Elasticsearch\Common\Exceptions\Missing404Exception;
use SNOWGIRL_CORE\Helper\Arrays;
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

    public function indexMany(string $index, array $documents): int
    {
        if (!$documents) {
            return 0;
        }

        $input = ['body' => []];

        $indexName = $this->indexer->makeIndexName($index);

        foreach ($documents as $id => $document) {
            $input['body'][] = [
                'index' => [
                    '_index' => $indexName,
                    '_id' => $id,
                ]
            ];

            $input['body'][] = $document;
        }

//        $this->updateIndex($index, ['refresh_interval' => -1, 'number_of_replicas' => 0]);

//        $input['refresh'] = true;

        $output = $this->indexer->getClient()->bulk($input);

//        $this->updateIndex($index, ['refresh_interval' => null, 'number_of_replicas' => 1]);
//        $this->forceMerge($index);

        if (!is_array($output) || !array_key_exists('items', $output) || !is_array($output['items'])) {
            return false;
        }

        return count(array_filter($output['items'], function ($item) {
            return is_array($item) &&
                array_key_exists('index', $item) &&
                is_array($item['index']) &&
                array_key_exists('result', $item['index'])
                && 'created' == $item['index']['result'];
        }));
    }

    public function deleteIndex(string $name): bool
    {
        try {
            $output = $this->indexer->getClient()->indices()->delete([
                'index' => $this->indexer->makeIndexName($name)
            ]);
        } catch (Missing404Exception $e) {
            return true;
        } catch (Throwable $e) {
            $this->indexer->getLogger()->error($e);
            return false;
        }

        return is_array($output) && array_key_exists('acknowledged', $output) && 1 == $output['acknowledged'];
    }

    public function switchAliasIndex(string $alias, string $index = null, callable $work = null)
    {
        $oldIndexes = $this->indexer->getAliasIndexes($alias);

        if ($index) {
            $newIndex = $index;
        } else {
            $newIndex = $alias . '_' . time();

            if ($oldIndexes) {
                $created = $this->createLikeIndex($oldIndexes[0], $newIndex);
            } else {
                $created = $this->createIndex($newIndex);
            }

            if (!$created) {
                return false;
            }
        }

        $output = $work ? call_user_func($work, $newIndex) : true;

        if (!$this->updateAliasIndexes($alias, $newIndex, $oldIndexes)) {
            $this->deleteIndex($newIndex);
            return false;
        }

        foreach ($oldIndexes as $oldIndex) {
            $this->deleteIndex($oldIndex);
        }

        return $output;
    }


    public function createIndex(string $name, array $mappings = []): bool
    {
        $body = [];

        if ($mappings) {
            $body['mappings'] = $mappings;
        }

        $output = $this->indexer->getClient()->indices()->create([
            'index' => $this->indexer->makeIndexName($name),
            'body' => $body
        ]);

        return is_array($output) && array_key_exists('acknowledged', $output) && 1 == $output['acknowledged'];
    }

    private function getIndexMappings(string $name): array
    {
        $index = $this->indexer->makeIndexName($name);

        return $this->indexer->walkRawSearchResults($this->indexer->getClient()->indices()->getMapping([
            'index' => $index
        ]), [
            $index,
            'mappings'
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
                'settings' => $settings
            ]
        ]);

        return is_array($output) && array_key_exists('acknowledged', $output) && 1 == $output['acknowledged'];
    }

    private function updateAliasIndexes(string $alias, $addIndex = [], $removeIndex = []): bool
    {
        $alias = $this->indexer->makeIndexName($alias);
        $actions = [];

        foreach (Arrays::cast($removeIndex) as $name) {
            $actions[] = ['remove' => ['index' => $this->indexer->makeIndexName($name), 'alias' => $alias]];
        }

        foreach (Arrays::cast($addIndex) as $name) {
            $actions[] = ['add' => ['index' => $this->indexer->makeIndexName($name), 'alias' => $alias]];
        }

        $output = $this->indexer->getClient()->indices()->updateAliases([
            'body' => ['actions' => $actions]
        ]);

        return is_array($output) && array_key_exists('acknowledged', $output) && true == $output['acknowledged'];
    }
}
