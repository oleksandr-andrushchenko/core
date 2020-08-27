<?php

namespace SNOWGIRL_CORE\Indexer;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Psr\Log\LoggerInterface;
use Throwable;

class ElasticIndexer implements IndexerInterface
{
    private $host;
    private $port;
    private $prefix;
    private $service;
    /**
     * @var LoggerInterface
     */
    private $logger;

    private $elasticsearch;

    public function __construct(string $host, int $port, string $prefix, string $service, LoggerInterface $logger)
    {
        $this->host = $host;
        $this->port = $port;
        $this->prefix = $prefix;
        $this->service = $service;
        $this->logger = $logger;
    }

    public function __destruct()
    {
        $this->elasticsearch = null;
    }

    public function makeQueryString(string $query, bool $prefix = false): string
    {
        $query = str_replace(['+', '-', '=', '&&', '||', '>', '<', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', '\\', '/'], '', $query);

        $query = explode(' ', $query);
        $query = array_map('trim', $query);

        $query = array_filter($query, function ($peace) {
            return '' !== $peace;
        });

        $tmp = [];

        for ($i = 0, $l = count($query); $i < $l; $i++) {
            if (in_array($query[$i], ['not', 'NOT'])) {
                $tmp[] = 'NOT ' . ($prefix ? '' : '*') . $query[++$i] . '*';
            } else {
                $tmp[] = ($prefix ? '' : '*') . $query[$i] . '*';
            }
        }

        $query = implode(' AND ', $tmp);

        return $query;
    }


    public function search(string $index, array $boolFilter, int $offset = 0, int $limit = 10, array $sort = [], array $columns = []): array
    {
        $params = [];

        $this->addBody($params)
            ->addFrom($params['body'], $offset)
            ->addSize($params['body'], $limit)
            ->addQueryBoolFilter($params['body'], $boolFilter);

        if ($sort) {
            $this->addSort($params, $sort);
        }

        if ($columns) {
            $this->addSourceIncludes($params, $columns);
        }

        $output = $this->searchRaw($index, $params);

        return $this->extractHits($output, $columns);
    }

    public function count(string $index, array $boolFilter)
    {
        $params = [
            'index' => $this->makeIndexName($index),
        ];

        $this->addBody($params)
            ->addQueryBoolFilter($params['body'], $boolFilter);

        $output = $this->getClient()->count($params);

        return is_array($output) && array_key_exists('count', $output) ? $output['count'] : null;
    }

    public function countRaw(string $index, array $params)
    {
        $params['index'] = $this->makeIndexName($index);

        try {
            $output = $this->getClient()->count($params);

            return is_array($output) && array_key_exists('count', $output) ? $output['count'] : null;
        } catch (Throwable $e) {
            $this->logger->error($e, compact('index', 'params'));
            return null;
        }
    }

    public function searchRaw(string $index, array $params, array $paths = [])
    {
        $params['index'] = $this->makeIndexName($index);

        try {
            $output = $this->getClient()->search($params);

            foreach ($paths as $path) {
                if (is_array($output) && array_key_exists($path, $output)) {
                    $output = $output[$path];
                } else {
                    return null;
                }
            }

            return $output;
        } catch (Throwable $e) {
            $this->logger->error($e, compact('index', 'params', 'paths'));
            return [];
        }
    }

    public function searchIds(string $index, array $params): array
    {
        $params['_source'] = false;

        $raw = $this->searchRaw($index, $params);

        return array_map(function ($item) {
            return $item['_id'];
        }, $this->walkRawSearchResults($raw, ['hits', 'hits']));
    }


    public function addBody(array &$params = []): IndexerInterface
    {
        $params['body'] = [];

        return $this;
    }

    public function addFrom(array &$params, int $from): IndexerInterface
    {
        $params['from'] = $from;

        return $this;
    }

    public function addSize(array &$params, int $size): IndexerInterface
    {
        $params['size'] = $size;

        return $this;
    }

    public function walkRawSearchResults($output, array $paths)
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


    public function addQueryBoolFilter(array &$params, array $filter): IndexerInterface
    {
        $params['query'] = [
            'bool' => [
                'filter' => $filter,
            ],
        ];

        return $this;
    }

    public function addQueryQueryString(array &$params, string $query, $field): IndexerInterface
    {
        $params['query'] = [
            'query_string' => [
                'query' => $query,
                is_array($field) ? 'fields' : 'default_field' => $field,
//                'analyze_wildcard' => true,
//                'allow_leading_wildcard' => '*' === $query[0],
//                'fuzzy_transpositions' => false
            ],
        ];

        return $this;
    }

    public function addAggsTerms(array &$params, string $field, int $size = 999999): IndexerInterface
    {
        $params['aggs'] = [
            $field => [
                'terms' => [
                    'field' => $field,
                    'order' => ['_count' => 'desc'],
                    'size' => $size,
                ],
            ],
        ];

        return $this;
    }

    public function addAggsNestedAggsTerms(array &$params, string $path, string $field, int $size = 999999): IndexerInterface
    {
        $params['aggs'] = [
            $path => [
                'nested' => [
                    'path' => $path,
                ],
            ],
        ];

        $this->addAggsTerms($params['aggs'][$path], $field, $size);

        return $this;
    }

    public function addAggsNestedBoolFilteredAggsTerms(array &$params, string $path, array $boolFilter, string $field, int $size = 999999): IndexerInterface
    {
        $params['aggs'] = [
            $path => [
                'nested' => [
                    'path' => $path,
                ],
                'aggs' => [
                    'filtered' => [
                        'filter' => [
                            'bool' => [
                                'filter' => $boolFilter,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->addAggsTerms($params['aggs'][$path]['aggs']['filtered'], $field, $size);

        return $this;
    }

    public function addNestedBoolFilterNode(array &$params, string $path, array $boolFilter): IndexerInterface
    {
        $params[] = [
            'nested' => [
                'path' => $path,
                'query' => [
                    'bool' => [
                        'filter' => $boolFilter,
                    ],
                ],
            ],
        ];

        return $this;
    }

    public function addSourceIncludes(array &$params, array $columns): IndexerInterface
    {
        $params['_source_includes'] = array_values($columns);

        return $this;
    }

    public function addSort(array &$params, $sort): IndexerInterface
    {
        $params['sort'] = $sort;

        return $this;
    }


    public function indexOne(string $index, $id, array $document): bool
    {
        $output = $this->getClient()->index([
            'index' => $this->makeIndexName($index),
            'type' => '_doc',
            'id' => $id,
            'body' => $document,
//            'refresh' => true,
        ]);

        return is_array($output) && array_key_exists('result', $output) && 'created' == $output['result'];
    }

    public function updateOne(string $index, $id, array $document): bool
    {
        $output = $this->getClient()->update([
            'index' => $this->makeIndexName($index),
            'type' => '_doc',
            'id' => $id,
            'body' => [
                'doc' => $document,
            ],
//            'refresh' => true,
        ]);

        return is_array($output) && array_key_exists('result', $output) && 'updated' == $output['result'];
    }

    public function deleteOne(string $index, $id): bool
    {
        $output = $this->getClient()->delete([
            'index' => $this->makeIndexName($index),
            'type' => '_doc',
            'id' => $id,
        ]);

        return is_array($output) && array_key_exists('result', $output) && 'deleted' == $output['result'];
    }


    public function getManager(): IndexerManagerInterface
    {
        return new ElasticIndexerManager($this);
    }


    private function extractHits(array $raw, array $columns = []): array
    {
        $raw = $this->walkRawSearchResults($raw, ['hits', 'hits']);

        if (null === $raw) {
            return [];
        }

        $output = [];

        //@todo split based on $columns structure
        foreach ($raw as $item) {
            if (is_array($item) &&
                array_key_exists('_id', $item) &&
                array_key_exists('_source', $item) &&
                is_array($item['_source'])) {

                $tmp = $item['_source'];
                $tmp['_id'] = $item['_id'];

                if ($columns) {
                    $item = [];

                    foreach ($columns as $k => $v) {
                        if (is_int($k)) {
                            $item[$v] = $tmp[$v];
                        } else {
                            $tmp2 = $tmp;

                            foreach (explode('.', $v) as $peace) {
                                if (array_key_exists($peace, $tmp2)) {
                                    $tmp2 = $tmp2[$peace];
                                } else {
                                    $tmp2 = null;
                                    $this->logger->warning("'$peace' peace key not found", [
                                        'item' => $item,
                                        'column_key' => $k,
                                        'column_value' => $v,
                                    ]);
                                    break;
                                }
                            }

                            $item[$k] = $tmp2;
                        }
                    }
                } else {
                    $item = $tmp;
                }

                $output[] = $item;
            }
        }

        return $output;
    }

    /**
     * @return Client
     */
    public function getClient(): Client
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

    public function makeIndexName(string $name): string
    {
        return $this->prefix . '_' . $name;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function makeName(string $index): string
    {
        return preg_replace("/^{$this->getPrefix()}_/", '', $index);
    }
}
