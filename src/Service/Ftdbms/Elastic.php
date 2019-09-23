<?php

namespace SNOWGIRL_CORE\Service\Ftdbms;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use SNOWGIRL_CORE\Helper\Arrays;
use SNOWGIRL_CORE\Service\Ftdbms;
use SNOWGIRL_CORE\Service\Storage\Query;

/**
 * Class Elastic
 *
 * @property Client client
 * @method Client getClient($force = false)
 * @method Client client()
 * @package SNOWGIRL_CORE\Service\Ftdbms
 */
class Elastic extends Ftdbms
{
    /**
     * @todo...
     *
     * @param null $key
     *
     * @return array[]
     */
    public function reqToArrays($key = null)
    {
        return $this->client()->reqToArrays($key);
    }

    /**
     * @todo...
     * @return array
     */
    public function reqToArray()
    {
        return $this->client()->reqToArray();
    }

    /**
     * @todo...
     *
     * @param      $className
     * @param null $key
     *
     * @return array
     */
    public function reqToObjects($className, $key = null)
    {
        return $this->client()->reqToObjects($className, $key);
    }

    protected $socket;
    protected $prefix;

    protected $service;

    protected $masterServices = true;

    protected function _getClient()
    {
        $logger = new Logger('log');
        $handler = new StreamHandler($this->logger->getName(), $this->logger->isOn() ? Logger::DEBUG : Logger::WARNING);
        $logger->pushHandler($handler);

        return ClientBuilder::fromConfig([
            'hosts' => [[
                'host' => $this->host,
                'port' => $this->port
            ]],
            'logger' => $logger
//            'retries' => 2,
//            'handler' => ClientBuilder::multiHandler()
        ], true);
    }

    protected function _dropClient()
    {
    }

    /**
     * @todo...
     *
     * @param Query $query
     *
     * @return $this|Ftdbms|\SNOWGIRL_CORE\Service\Storage
     */
    public function _req(Query $query)
    {
        $tmp = explode('?', $query->text);
        $query->text = '';

        foreach ($query->params as $k => $v) {
            $query->text .= $tmp[$k] . (is_string($v) ? "'$v'" : $v);
        }

        $query->text .= end($tmp);
        $query->params = [];

        $this->client()->req($query);

        return $this;
    }

    /**
     * @todo...
     *
     * @param            $what
     * @param Query|null $query
     *
     * @return array
     */
    public function selectMany($what, Query $query = null)
    {
        $query = Query::normalize($query);
        $query->params = [];

        $query->text = implode(' ', [
            $this->client()->makeSelectSQL($query->columns, false, $query->params),
            $this->client()->makeFromSQL(array_map(function ($table) {
                return $this->makeIndexName($table);
            }, (array)$what)),
            $query->where ? $this->client()->makeWhereSQL($query->where, $query->params) : '',
            $query->groups ? $this->client()->makeGroupSQL($query->groups, $query->params) : '',
            $query->orders ? $this->client()->makeOrderSQL($query->orders, $query->params) : '',
            false === $query->limit ? '' : $this->makeLimitSQL($query->offset, $query->limit, $query->params),
            false === $query->limit ? '' : $this->makeOptionsSQL($query->offset, $query->limit, $query->params)
        ]);

        return $this->req($query)->reqToArrays();
    }

    /**
     * @todo...
     *
     * @param            $what
     * @param array      $values
     * @param Query|null $query
     *
     * @return int
     */
    public function updateMany($what, array $values, Query $query = null)
    {
        return $this->connect()->client()->updateMany($this->makeIndexName($what), $values, $query);
    }

    /**
     * @todo...
     *
     * @param            $what
     * @param Query|null $query
     *
     * @return array|int|int[]
     */
    public function selectCount($what, Query $query = null)
    {
        $query = $query ? $query : new Query;

        $query->columns = [];

        if ($query->groups) {
            $query->columns[] = new Expr('@groupby AS ' . $this->client()->quote($query->groups));
        }

        $query->columns[] = new Expr('COUNT(*) AS ' . $this->client()->quote('cnt'));
        $query->limit = false;

        $tmp = $this->selectMany($what, $query);

        if ($query->groups) {
            $output = [];

            foreach ($tmp as $i) {
                $output[$i[$query->groups]] = (int)$i['cnt'];
            }

            return $output;
        }

        $output = (int)$tmp[0]['cnt'];

        return $output;
    }

    public function foundRows()
    {
        foreach ($this->req(new Query('SHOW META'))->reqToArrays() as $i) {
            if ('total_found' == $i['Variable_name']) {
                return (int)$i['Value'];
            }
        }

        return null;
    }

    public function numRows()
    {
        // TODO: Implement numRows() method.
    }

    public function insertedId()
    {
        // TODO: Implement insertedId() method.
    }

    public function insertOne($what, array $values, Query $query = null)
    {
        // TODO: Implement insertOne() method.
    }

    public function insertMany($what, array $values, Query $query = null)
    {
        // TODO: Implement insertMany() method.
    }

    public function deleteOne($what, Query $query = null)
    {
        return $this->client()->delete([
            'index' => $this->makeIndexName($what)
        ]);
    }

    public function deleteMany($what, Query $query = null)
    {
        return $this->client()->deleteByQuery([
            'index' => $this->makeIndexName($what)
        ]);
    }

    public function affectedRows()
    {
        // TODO: Implement affectedRows() method.
    }

    public function warnings()
    {
        // TODO: Implement warnings() method.
    }

    public function errors()
    {
        // TODO: Implement errors() method.
    }

    public function getErrors(\Exception $ex = null)
    {
        // TODO: Implement getErrors() method.
    }

    public function createIndex(string $name, array $mappings = []): bool
    {
        $body = [];

        if ($mappings) {
            $body['mappings'] = $mappings;
        }

        $output = $this->client()->indices()->create([
            'index' => $this->makeIndexName($name),
            'body' => $body
        ]);

        return is_array($output) && array_key_exists('acknowledged', $output) && 1 == $output['acknowledged'];
    }

    public function createLikeIndex(string $index, string $newIndex): bool
    {
        return $this->createIndex($newIndex, $this->getIndexMappings($index));
    }

    public function updateIndex(string $name, array $settings): bool
    {
        $output = $this->client()->indices()->putSettings([
            'index' => $this->makeIndexName($name),
            'body' => [
                'settings' => $settings
            ]
        ]);

        return is_array($output) && array_key_exists('acknowledged', $output) && 1 == $output['acknowledged'];
    }

    public function forceMerge(string $index)
    {
        return $this->client()->indices()->forceMerge(['index' => $this->makeIndexName($index)]);
    }

    public function indexOne(string $index, $id, array $document): bool
    {
        $output = $this->client()->index([
            'index' => $this->makeIndexName($index),
//            'type' => '_doc',
            'id' => $id,
            'body' => $document
        ]);

        return is_array($output) && array_key_exists('created', $output) && 1 == $output['created'];
    }

    public function indexMany(string $index, array $documents): int
    {
        if (!$documents) {
            return 0;
        }

        $input = ['body' => []];

        foreach ($documents as $id => $document) {
            $input['body'][] = [
                'index' => [
                    '_index' => $this->makeIndexName($index),
                    '_id' => $id
                ]
            ];

            $input['body'][] = $document;
        }

        $this->updateIndex($index, ['refresh_interval' => -1, 'number_of_replicas' => 0]);
        $output = $this->client()->bulk($input);
        $this->updateIndex($index, ['refresh_interval' => null, 'number_of_replicas' => 1]);
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

    public function getOne(string $index, $id): ?array
    {
        $output = $this->client()->get([
            'index' => $this->makeIndexName($index),
            'id' => $id
        ]);

        return is_array($output) &&
        array_key_exists('found', $output) &&
        true === $output['found'] &&
        array_key_exists('_source', $output) &&
        is_array($output['_source'])
            ? $output['_source']
            : null;
    }

    /**
     * @param string $index
     * @param array  $boolFilter
     * @param int    $offset
     * @param int    $limit
     * @param array  $sort
     * @param array  $columns
     *
     * @return array
     */
    public function search(string $index, array $boolFilter, int $offset = 0, int $limit = 10, array $sort = [], array $columns = [])
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
            'index' => $this->makeIndexName($index)
        ];

        $this->addBody($params)
            ->addQueryBoolFilter($params['body'], $boolFilter);

        $output = $this->client()->count($params);

        return is_array($output) && array_key_exists('count', $output) ? $output['count'] : null;
    }

    public function countRaw(string $index, array $params)
    {
        $params['index'] = $this->makeIndexName($index);

        $output = $this->client()->count($params);

        return is_array($output) && array_key_exists('count', $output) ? $output['count'] : null;
    }

    public function searchRaw(string $index, array $params, array $paths = [])
    {
        $params['index'] = $this->makeIndexName($index);

        $output = $this->client()->search($params);

        foreach ($paths as $path) {
            if (is_array($output) && array_key_exists($path, $output)) {
                $output = $output[$path];
            } else {
                return null;
            }
        }

        return $output;
    }

    public function searchHits(string $index, array $params, array $columns = [])
    {
        if ($columns) {
            $this->addSourceIncludes($params, $columns);
        }

        $raw = $this->searchRaw($index, $params);

        return $this->extractHits($raw, $columns);

    }

    public function searchColumn(string $index, array $params, string $column): array
    {
        $this->addSourceIncludes($params, [$column]);

        $raw = $this->searchRaw($index, $params);

        return array_map(function ($item) use ($column) {
            return $item[$column];
        }, $this->extractHits($raw, [$column]));
    }

    public function searchIds(string $index, array $params): array
    {
        $params['_source'] = false;

        $raw = $this->searchRaw($index, $params);

        return array_map(function ($item) {
            return $item['_id'];
        }, $this->walkRawSearchResults($raw, ['hits', 'hits']));
    }

    public function addBody(array &$params = []): self
    {
        $params['body'] = [];
        return $this;
    }

    public function addFrom(array &$params, int $from): self
    {
        $params['from'] = $from;
        return $this;
    }

    public function addSize(array &$params, int $size): self
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

    /**
     * @todo optimize
     *
     * @param array $raw
     * @param array $columns
     *
     * @return array
     */
    public function extractHits(array $raw, array $columns = []): array
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
                                $tmp2 = $tmp2[$peace];
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

    public function getMany(string $index, array $ids): array
    {
        $output = array_combine($ids, array_fill(0, count($ids), null));

        $output2 = $this->search($index, [
            'ids' => [
                'values' => $ids
            ]
        ]);

        if (!is_array($output2) ||
            !array_key_exists('hits', $output2) ||
            !is_array($output2['hits']) ||
            !array_key_exists('hits', $output2['hits']) ||
            !is_array($output2['hits']['hits'])) {
            return $output;
        }

        foreach ($output2['hits']['hits'] as $item) {
            if (is_array($item) &&
                array_key_exists('_id', $item) &&
                array_key_exists('_source', $item) &&
                is_array($item['_source'])) {
                $output[$item['_id']] = $item['_source'];
            }
        }

        return $output;
    }

    protected function makeIndexName(string $name): string
    {
        return $this->prefix . '_' . $name;
    }

    protected function makeName(string $index): string
    {
        return preg_replace("/^{$this->prefix}_/", '', $index);
    }

    public function deleteIndex(string $name): bool
    {
        try {
            $output = $this->client()->indices()->delete([
                'index' => $this->makeIndexName($name)
            ]);
        } catch (Missing404Exception $ex) {
            return true;
        }

        return is_array($output) && array_key_exists('acknowledged', $output) && 1 == $output['acknowledged'];
    }

    public function addQueryBoolFilter(array &$params, array $filter): self
    {
        $params['query'] = [
            'bool' => [
                'filter' => $filter
            ]
        ];

        return $this;
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html
     *
     * @param array  $params
     * @param string $query - need to be escaped!!!
     * @param        $field
     *
     * @return Elastic
     */
    public function addQueryQueryString(array &$params, string $query, $field): self
    {
        $params['query'] = [
            'query_string' => [
                'query' => $query,
                is_array($field) ? 'fields' : 'default_field' => $field,
//                'analyze_wildcard' => true,
//                'allow_leading_wildcard' => '*' === $query[0],
//                'fuzzy_transpositions' => false
            ]
        ];

        return $this;
    }

    public function addAggsTerms(array &$params, string $field, int $size = 999999): self
    {
        $params['aggs'] = [
            $field => [
                'terms' => [
                    'field' => $field,
                    'order' => ['_count' => 'desc'],
                    'size' => $size
                ]
            ]
        ];

        return $this;
    }

    public function addAggsNestedAggsTerms(array &$params, string $path, string $field, int $size = 999999): self
    {
        $params['aggs'] = [
            $path => [
                'nested' => [
                    'path' => $path
                ]
            ]
        ];

        $this->addAggsTerms($params['aggs'][$path], $field, $size);

        return $this;
    }

    public function addAggsNestedBoolFilteredAggsTerms(array &$params, string $path, array $boolFilter, string $field, int $size = 999999): self
    {
        $params['aggs'] = [
            $path => [
                'nested' => [
                    'path' => $path
                ],
                'aggs' => [
                    'filtered' => [
                        'filter' => [
                            'bool' => [
                                'filter' => $boolFilter
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->addAggsTerms($params['aggs'][$path]['aggs']['filtered'], $field, $size);

        return $this;
    }

    public function addNestedBoolFilterNode(array &$params, string $path, array $boolFilter): self
    {
        $params[] = [
            'nested' => [
                'path' => $path,
                'query' => [
                    'bool' => [
                        'filter' => $boolFilter
                    ]
                ]
            ]
        ];

        return $this;
    }

    public function addSourceIncludes(array &$params, array $columns): self
    {
        $params['_source_includes'] = array_values($columns);

        return $this;
    }

    public function addSort(array &$params, $sort): self
    {
        $params['sort'] = $sort;
        return $this;
    }

    public function getAliasIndexes(string $alias, $withAliasOnly = false): array
    {
        try {
            $output = $this->client()->indices()->getAliases([
                'name' => $this->makeIndexName($alias)
            ]);
        } catch (Missing404Exception $ex) {
            return [];
        }

        if (is_array($output)) {
            return array_map(function ($index) {
                return $this->makeName($index);
            }, array_keys($withAliasOnly ? array_filter($output, function ($raw) {
                return 1 == count($raw['aliases']);
            }) : $output));
        }

        return [];
    }

    public function createIndexAlias(string $index, string $alias)
    {
        $output = $this->client()->indices()->putAlias([
            'index' => $this->makeIndexName($index),
            'name' => $this->makeIndexName($alias)
        ]);

        var_dump($output);
        die;

        return $output;
    }

    public function updateAliasIndexes(string $alias, $addIndex = [], $removeIndex = []): bool
    {
        $alias = $this->makeIndexName($alias);
        $actions = [];

        foreach (Arrays::cast($removeIndex) as $name) {
            $actions[] = ['remove' => ['index' => $this->makeIndexName($name), 'alias' => $alias]];
        }

        foreach (Arrays::cast($addIndex) as $name) {
            $actions[] = ['add' => ['index' => $this->makeIndexName($name), 'alias' => $alias]];
        }

        $output = $this->client()->indices()->updateAliases([
            'body' => ['actions' => $actions]
        ]);

        return is_array($output) && array_key_exists('acknowledged', $output) && true == $output['acknowledged'];
    }

    public function switchAliasIndex(string $alias, string $index = null, callable $work = null)
    {
        $oldIndexes = $this->getAliasIndexes($alias);

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

    public function getIndexMappings(string $name): array
    {
        $index = $this->makeIndexName($name);

        return $this->walkRawSearchResults($this->client()->indices()->getMapping([
            'index' => $index
        ]), [
            $index,
            'mappings'
        ]);
    }

    public function getIndexSettings(string $name): array
    {
        $index = $this->makeIndexName($name);

        return $this->walkRawSearchResults($this->client()->indices()->getSettings([
            'index' => $index
        ]), [$index, 'settings', 'index']);
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
}
