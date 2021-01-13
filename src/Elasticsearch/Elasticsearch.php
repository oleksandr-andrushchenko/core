<?php

namespace SNOWGIRL_CORE\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Psr\Log\LoggerInterface;
use Throwable;

class Elasticsearch implements ElasticsearchInterface
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Client
     */
    private $elasticsearch;

    public function __construct(string $host, int $port, string $prefix, LoggerInterface $logger)
    {
        $this->host = $host;
        $this->port = $port;
        $this->prefix = $prefix;
        $this->logger = $logger;
    }

    public function __destruct()
    {
        $this->elasticsearch = null;
    }

    public function search(string $index, ElasticsearchQuery $query): array
    {
        $params = $this->makeSearchParams($index, $query);

        return $this->searchByParams($index, $params, $query);
    }

    public function searchByParams(string $index, array $params, ElasticsearchQuery $query = null): array
    {
        $defaultParams = $this->makeSearchParams($index, $query);
        $params = array_merge($defaultParams, $params);

        try {
            $response = $this->getClient()->search($params);

            if ($query) {
                if ($query->paths) {
                    return $this->findValueByPath($response, $query->paths);
                }

                $items = $this->findValueByPath($response, ['hits', 'hits']);

                if (null === $items) {
                    return [];
                }

                if ($query->idsOnly) {
                    $output = [];

                    foreach ($items as $item) {
                        $output[] = $item['_id'];
                    }

                    return $output;
                }

                if ($query->idsAsKeys) {
                    $output = [];

                    foreach ($items as $item) {
                        $output[$item['_id']] = $item['_source'];
                    }

                    return $output;
                }
            }

            $items = $this->findValueByPath($response, ['hits', 'hits']);

            if (null === $items) {
                return [];
            }

            $output = [];

            foreach ($items as $item) {
                $output[] = $item['_source'];
            }

            return $output;
        } catch (Throwable $exception) {
            $this->logger->error($exception, compact('index', 'params'));
            return [];
        }
    }

    public function count(string $index, ElasticsearchQuery $query): int
    {
        $params = $this->makeCountParams($index, $query);

        return $this->countByParams($index, $params, $query);
    }

    public function countByParams(string $index, array $params, ElasticsearchQuery $query = null): int
    {
        $defaultParams = $this->makeCountParams($index, $query);
        $params = array_merge($defaultParams, $params);

        try {
            $response = $this->getClient()->count($params);

            return is_array($response) && array_key_exists('count', $response) ? $response['count'] : 0;
        } catch (Throwable $exception) {
            $this->logger->error($exception, compact('index', 'params'));
            return 0;
        }
    }

    public function indexOne(string $index, $id, array $document): bool
    {
        $response = $this->getClient()->index([
            'index' => $this->makeIndex($index),
            'type' => '_doc',
            'id' => $id,
            'body' => $document,
//            'refresh' => true,
        ]);

        return is_array($response) && array_key_exists('result', $response) && 'created' == $response['result'];
    }

    public function updateOne(string $index, $id, array $document): bool
    {
        $response = $this->getClient()->update([
            'index' => $this->makeIndex($index),
            'type' => '_doc',
            'id' => $id,
            'body' => [
                'doc' => $document,
            ],
//            'refresh' => true,
        ]);

        return is_array($response) && array_key_exists('result', $response) && 'updated' == $response['result'];
    }

    public function deleteOne(string $index, $id): bool
    {
        $response = $this->getClient()->delete([
            'index' => $this->makeIndex($index),
            'type' => '_doc',
            'id' => $id,
        ]);

        return is_array($response) && array_key_exists('result', $response) && 'deleted' == $response['result'];
    }

    public function indexMany(string $name, array $documents): int
    {
        if (!$documents) {
            return 0;
        }

        $params = ['body' => []];

        $index = $this->makeIndex($name);

        foreach ($documents as $id => $document) {
            $params['body'][] = [
                'index' => [
                    '_index' => $index,
                    '_id' => $id,
                ],
            ];

            $params['body'][] = $document;
        }

        try {
            $response = $this->getClient()->bulk($params);

            if (is_array($response) && array_key_exists('items', $response) && is_array($response['items'])) {
                $this->logger->info('Documents has been indexed', compact('name', 'index'));
            } else {
                $this->logger->error('Documents has not been indexed', compact('name', 'index', 'documents', 'output'));
                return false;
            }

            return count(array_filter($response['items'], function ($item) {
                return is_array($item) &&
                    array_key_exists('index', $item) &&
                    is_array($item['index']) &&
                    array_key_exists('result', $item['index'])
                    && 'created' == $item['index']['result'];
            }));
        } catch (Missing404Exception $exception) {
            $this->logger->warning('Trying to index documents on non-existing index', compact('name', 'index'));
            return 0;
        } catch (Throwable $exception) {
            $this->logger->error($exception);
            return 0;
        }
    }

    public function deleteManyByDocumentIds(string $index, array $ids): int
    {
        if (!$ids) {
            return 0;
        }

        $params = ['body' => []];

        $normalizedIndex = $this->makeIndex($index);

        foreach ($ids as $id) {
            $params['body'][] = [
                'delete' => [
                    '_index' => $normalizedIndex,
                    '_id' => $id,
                ],
            ];
        }

        try {
            $response = $this->getClient()->bulk($params);

            if (is_array($response) && array_key_exists('items', $response) && is_array($response['items'])) {
                $this->logger->info('Documents has been deleted', compact('name', 'normalizedIndex'));
            } else {
                $this->logger->error('Documents has not been deleted', compact('name', 'normalizedIndex', 'documents', 'output'));
                return 0;
            }

            return count(array_filter($response['items'], function ($item) {
                return is_array($item) &&
                    array_key_exists('delete', $item) &&
                    is_array($item['delete']) &&
                    array_key_exists('result', $item['delete'])
                    && 'deleted' == $item['delete']['result'];
            }));
        } catch (Missing404Exception $exception) {
            $this->logger->warning('Trying to delete documents on non-existing index', compact('name', 'normalizedIndex'));
            return 0;
        } catch (Throwable $exception) {
            $this->logger->error($exception);
            return 0;
        }
    }

    public function deleteByParams(string $index, array $params, ElasticsearchQuery $query=null): int
    {
        $normalizedIndex = $this->makeIndex($index);

        $params = [
            'index' => $normalizedIndex,
            'body' => [
                'query' => $query,
            ],
        ];

        try {
            $response = $this->getClient()->deleteByQuery($params);

            var_dump($response);
            die;

            if (is_array($output) && array_key_exists('items', $output) && is_array($output['items'])) {
                $this->logger->info('Documents has been deleted', compact('name', 'normalizedIndex'));
            } else {
                $this->logger->error('Documents has not been deleted', compact('name', 'normalizedIndex', 'documents', 'output'));
                return 0;
            }

            return count(array_filter($output['items'], function ($item) {
                return is_array($item) &&
                    array_key_exists('delete', $item) &&
                    is_array($item['delete']) &&
                    array_key_exists('result', $item['delete'])
                    && 'deleted' == $item['delete']['result'];
            }));
        } catch (Missing404Exception $exception) {
            $this->logger->warning('Trying to delete documents on non-existing index', compact('name', 'normalizedIndex'));
            return 0;
        } catch (Throwable $exception) {
            $this->logger->error($exception);
            return 0;
        }
    }

    public function deleteIndex(string $name): bool
    {
        $index = $this->makeIndex($name);

        try {
            $output = $this->getClient()->indices()->delete([
                'index' => $index,
            ]);

            $result = is_array($output) && array_key_exists('acknowledged', $output) && true == $output['acknowledged'];

            if ($result) {
                $this->logger->info('Index has been deleted', compact('name', 'index'));
            } else {
                $this->logger->error('Index has not been deleted', compact('name', 'index', 'output'));
            }

            return $result;
        } catch (Missing404Exception $exception) {
            $this->logger->warning('Trying to delete non-existing index', compact('name', 'index'));
            return true;
        } catch (Throwable $exception) {
            $this->logger->error($exception);
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

        try {
            $output = $job ? call_user_func($job, $newAliasIndex) : true;
        } catch (Throwable $exception) {
            $this->logger->error($exception);
            $output = null;
        }

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

        $index = $this->makeIndex($name);

        try {
            $output = $this->getClient()->indices()->create([
                'index' => $index,
                'body' => $body,
            ]);

            $result = is_array($output) && array_key_exists('acknowledged', $output) && true == $output['acknowledged'];

            if ($result) {
                $this->logger->info('Index has been created', compact('name', 'index'));
            } else {
                $this->logger->error('Index has not been created', compact('name', 'index', 'mappings', 'output'));
            }

            return $result;
        } catch (Throwable $exception) {
            $this->logger->error($exception);
            return false;
        }
    }

    private function getClient(): Client
    {
        if (null === $this->elasticsearch) {
            $this->logger->debug(__FUNCTION__);

            $this->elasticsearch = ClientBuilder::fromConfig([
                'hosts' => [[
                    'host' => $this->host,
                    'port' => $this->port,
                ]],
//                'logger' => $this->logger,
                'tracer' => $this->logger,
            ], true);
        }

        return $this->elasticsearch;
    }

    private function makeIndex(string $index): string
    {
        return $this->prefix . '_' . $index;
    }

    private function makeName(string $index): string
    {
        return preg_replace("/^{$this->prefix}_/", '', $index);
    }

    private function findValueByPath($output, array $paths)
    {
        foreach ($paths as $path) {
            if (is_array($output) && array_key_exists($path, $output)) {
                $output = $output[$path];
            } else {
                return null;
            }
        }

        return $output;
    }

    private function makeSearchString(string $term, bool $prefix = false): ?string
    {
        if (!$term) {
            return null;
        }

        $searchString = str_replace(['+', '-', '=', '&&', '||', '>', '<', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', '\\', '/'], '', $term);

        $searchString = explode(' ', $searchString);
        $searchString = array_map('trim', $searchString);

        $searchString = array_filter($searchString, function ($peace) {
            return '' !== $peace;
        });

        $searchString = array_values($searchString);

        $tmp = [];

        for ($i = 0, $l = count($searchString); $i < $l; $i++) {
            if (in_array($searchString[$i], ['not', 'NOT'])) {
                $tmp[] = 'NOT ' . ($prefix ? '' : '*') . $searchString[++$i] . '*';
            } else {
                $tmp[] = ($prefix ? '' : '*') . $searchString[$i] . '*';
            }
        }

        $searchString = implode(' AND ', $tmp);

        return $searchString;
    }

    private function makeSearchParams(string $index, ElasticsearchQuery $query = null): array
    {
        $params = [
            'index' => $this->makeIndex($index),
        ];

        if (!$query) {
            return $params;
        }

        $params['body'] = [];

        if ($from = $this->makeFrom($query->from)) {
            $params['body']['from'] = $from;
        }

        if ($size = $this->makeSize($query->size)) {
            $params['body']['size'] = $size;
        }

        if ($sort = $this->makeSort($query->sort)) {
            $params['sort'] = $sort;
        }

        if ($queryBoolFilter = $query->queryBoolFilter) {
            $params['body']['query'] = [
                'bool' => [
                    'filter' => $queryBoolFilter,
                ],
            ];
        }

        if ($sourceIncludes = $query->sourceIncludes) {
            $params['_source_includes'] = $sourceIncludes;
        }

        if ($query->queryStringQuery && $queryString = $this->makeSearchString($query->queryStringQuery, $query->queryStringPrefix)) {
            $params['body']['query'] = [
                'query_string' => [
                    'query' => $queryString,
                    is_array($query->queryStringFields) ? 'fields' : 'default_field' => $query->queryStringFields,
                ],
            ];
        }

        if ($query->idsOnly) {
            $params['_source'] = false;
        }

        return $params;
    }

    private function makeCountParams(string $index, ElasticsearchQuery $query = null): array
    {
        $params = [
            'index' => $this->makeIndex($index),
        ];

        if (!$query) {
            return $params;
        }

        $params['body'] = [];

        if ($queryBoolFilter = $query->queryBoolFilter) {
            $params['body']['query'] = [
                'bool' => [
                    'filter' => $queryBoolFilter,
                ],
            ];
        }

        if ($query->queryStringQuery && $queryString = $this->makeSearchString($query->queryStringQuery, $query->queryStringPrefix)) {
            $params['body']['query'] = [
                'query_string' => [
                    'query' => $queryString,
                    is_array($query->queryStringFields) ? 'fields' : 'default_field' => $query->queryStringFields,
                ],
            ];
        }

        return $params;
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
        $index = $this->makeIndex($name);

        return $this->findValueByPath($this->getClient()->indices()->getMapping([
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
        $output = $this->getClient()->indices()->putSettings([
            'index' => $this->makeIndex($name),
            'body' => [
                'settings' => $settings,
            ],
        ]);

        return is_array($output) && array_key_exists('acknowledged', $output) && 1 == $output['acknowledged'];
    }

    private function updateAliasIndexes(string $name, array $addIndex = [], array $removeIndex = []): bool
    {
        $alias = $this->makeIndex($name);
        $actions = [];

        foreach ($removeIndex as $name) {
            $actions[] = [
                'remove' => [
                    'index' => $this->makeIndex($name),
                    'alias' => $alias,
                ],
            ];
        }

        foreach ($addIndex as $name) {
            $actions[] = [
                'add' => [
                    'index' => $this->makeIndex($name),
                    'alias' => $alias,
                ],
            ];
        }

        try {
            $output = $this->getClient()->indices()->updateAliases([
                'body' => [
                    'actions' => $actions,
                ],
            ]);

            $result = is_array($output) && array_key_exists('acknowledged', $output) && true == $output['acknowledged'];

            if ($result) {
                $this->logger->info('Alias indexes has been updated', compact('name', 'alias'));
            } else {
                $this->logger->error('Alias indexes has not been updated', compact('name', 'alias', 'actions', 'output'));
            }

            return $result;
        } catch (Missing404Exception $exception) {
            $this->logger->warning('Trying to update non-existing indexes alias', compact('name', 'alias', 'actions'));
            return false;
        } catch (Throwable $exception) {
            $this->logger->error($exception);
            return false;
        }
    }

    private function getIndexes(string $name): array
    {
        $index = $this->makeIndex($name);

        try {
            $output = $this->getClient()->indices()->get([
                'index' => $index,
            ]);

            if (is_array($output)) {
                $this->logger->info('Indexes has been fetched', compact('name', 'index'));
            } else {
                $this->logger->error('Indexes has not been fetched', compact('name', 'index', 'output'));
                return [];
            }

            return array_map(function ($index) {
                return $this->makeName($index);
            }, array_keys($output));
        } catch (Missing404Exception $exception) {
            $this->logger->warning('Trying to fetch non-existing indexes', compact('name', 'index'));
            return [];
        } catch (Throwable $exception) {
            $this->logger->error($exception);
            return [];
        }
    }

    private function getAliasIndexes(string $name): array
    {
        $alias = $this->makeIndex($name);

        try {
            $output = $this->getClient()->indices()->getAliases([
                'name' => $alias,
            ]);

            if (is_array($output)) {
                $this->logger->info('Alias indexes has been fetched', compact('name', 'alias'));
            } else {
                $this->logger->error('Alias indexes has not been fetched', compact('name', 'alias', 'output'));
                return [];
            }

            return array_map(function ($index) {
                return $this->makeName($index);
            }, array_keys($output));
        } catch (Missing404Exception $exception) {
            $this->logger->warning('Trying to fetch non-existing alias indexes', compact('name', 'alias'));
            return [];
        } catch (Throwable $exception) {
            $this->logger->error($exception);
            return [];
        }
    }

    private function makeFrom($from = 0): int
    {
        return $from ? (int) $from : 0;
    }

    private function makeSize($size = 10): int
    {
        return $size ? (int) $size : 999999;
    }

    private function makeSort($sort): ?array
    {
        if (!$sort) {
            return null;
        }

        if (!is_array($sort) && !is_string($sort)) {
            $this->logger->warning('Invalid sort has been passed in ' . __FUNCTION__, [
                'sort' => var_export($sort, true),
            ]);
            return null;
        }

        $output = [];

        $map = [
            SORT_ASC => 'asc',
            SORT_DESC => 'desc',
        ];

        foreach (is_array($sort) ? $sort : [$sort] as $k => $v) {
            if (is_int($k)) {
                $output[] = "$v:asc";
            } elseif (isset($map[$v])) {
                $output[] = "$k:{$map[$v]}";
            } else {
                $this->logger->warning('Invalid sort direction has been passed in ' . __FUNCTION__, [
                    'key' => $k,
                    'value' => $v,
                ]);
            }
        }

        return $output;
    }
}
