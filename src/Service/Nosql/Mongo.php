<?php

namespace SNOWGIRL_CORE\Service\Nosql;

use MongoDB\Client;
use MongoDB\Driver\BulkWrite;

//use MongoDB\Driver\Cursor;
//use MongoDB\Driver\Query;
use MongoDB\Model\BSONDocument;
use SNOWGIRL_CORE\Service\Nosql;
use SNOWGIRL_CORE\Service\Storage\Query;

/**
 * @todo    cache $this->client()->selectDatabase()
 * Class Mongodb
 * @property Client client
 * @method Client getClient($force = false)
 * @method Client client()
 * @package SNOWGIRL_CORE\Service\Nosql
 */
class Mongo extends Nosql
{
    protected $schema;

    public function getSchema()
    {
        return $this->schema;
    }

    protected function _getClient()
    {
//        \MongoLog::setLevel(\MongoLog::ALL);
//        \MongoLog::setModule(\MongoLog::ALL);
//        \MongoLog::setCallback(function ($module, $level, $message) {
//            $this->log(implode("\r\n", [
//                'module: ' . $module,
//                'level: ' . $level,
//                'message: ' . $message
//            ]));
//        });

//        phpinfo();die;

//        ini_set('mongodb.debug', $this->logger->getName());
//        phpinfo();die;

        return new Client('mongodb://' . $this->host . ':' . $this->port, [], ['typeMap' => [
            'root' => 'array',
            'document' => 'array',
            'array' => 'array'
        ]]);
    }

    public function insertOne($what, array $values, Query $query = null)
    {
        return $this->client()
            ->selectCollection($this->getSchema(), $what)
            ->insertOne($values)
            ->getInsertedCount();
    }

    public function insertMany($what, array $values, Query $query = null)
    {
        //@todo test
        return $this->client()
            ->selectCollection($this->getSchema(), $what)
            ->insertMany($values)
            ->getInsertedCount();
    }

    public function select($what, Query $query = null)
    {
        return $this->debugIfNeed(function () use ($what, $query) {
            $query = Query::normalize($query);

            $this->log('select from ' . $what . ': ' . $query);

            return $this->client()
                ->selectCollection($this->getSchema(), $what)
                ->find($query->where ?: [], $this->makeOptions($query))
                ->toArray();
        });
    }

    public function aggregate($what, array $pipeline, array $options = [])
    {
        return $this->debugIfNeed(function () use ($what, $pipeline, $options) {
//            $this->log(__FUNCTION__ . ' from ' . $what . ': ' . (string)var_export($pipeline, true));
            $this->log('aggregate from ' . $what . ': ' . json_encode($pipeline));

//        $options = array_merge(['allowDiskUse' => false], $options);

            return $this->client()
                ->selectCollection($this->getSchema(), $what)
                ->aggregate($pipeline, $options)
                ->toArray();
        });
    }

    public function update($what, array $values, Query $query = null)
    {
        // TODO: Implement update() method.
    }

    public function delete($what, Query $query = null)
    {
        // TODO: Implement delete() method.
    }

    public function foundRows()
    {
        die('foundRows');
        // TODO: Implement foundRows() method.
    }

    public function numRows()
    {
        die('numRows');
        // TODO: Implement numRows() method.
    }

    public function selectCount($what, Query $query = null)
    {
        return $this->debugIfNeed(function () use ($what, $query) {
            $query = Query::normalize($query);

            $this->log('select count from ' . $what . ': ' . $query);

            return $this->client()
                ->selectCollection($this->getSchema(), $what)
                ->countDocuments($query->where ?: $query->filter);
        });
    }

    public function reqToArrays($key = null)
    {
        // TODO: Implement reqToArrays() method.
    }

    public function reqToObjects($className, $key = null)
    {
        // TODO: Implement reqToObjects() method.
    }

    public function affectedRows()
    {
        return null;
        // TODO: Implement affectedRows() method.
    }

    public function errors()
    {
        // TODO: Implement errors() method.
    }

    public function warnings()
    {
        // TODO: Implement warnings() method.
    }

    public function insertedId()
    {
        // TODO: Implement insertedId() method.
    }

    public function getErrors(\Exception $ex = null)
    {
        // TODO: Implement getErrors() method.
    }

    protected function _dropClient()
    {
        unset($this->client);
//        parent::_dropClient();
    }

    protected function _req(Query $query)
    {
        $query = new Query($query->filter);
        return $this->client()
            ->getManager()
            ->executeQuery(($query->schema ?: $this->schema) . '.' . $query->what, $query);
    }

    public function reqToArray()
    {
        // TODO: Implement reqToArray() method.
    }

    /**
     * @param      $what
     * @param null $schema
     *
     * @return bool
     * @throws \Exception
     */
    public function createCollection($what, $schema = null)
    {
        try {
            $arr = $this->client()
                ->selectDatabase($schema ?: $this->schema)
                ->createCollection($what);

            return isset($arr['ok']) && 1 == $arr['ok'];
        } catch (\Exception $ex) {
            //already exists
            if (48 != $ex->getCode()) {
                throw $ex;
            }

            return true;
        }
    }

    public function createTable($what, $schema = null)
    {
        return $this->createCollection($what, $schema);
    }

    public function dropSchema($schema = null)
    {
        /** @var BSONDocument $doc */
        $doc = $this->client()
            ->selectDatabase($schema ?: $this->schema)
            ->drop();
        $arr = $doc->getArrayCopy();

        return isset($arr['ok']) && (1 == $arr['ok']) && isset($arr['dropped']) && $schema == $arr['dropped'];
    }

    /**
     * @param          $what
     * @param \Closure $work
     * @param null     $schema
     *
     * @return \MongoDB\Driver\WriteResult
     */
    public function bulk($what, \Closure $work)
    {
        $bulk = new BulkWrite();
        call_user_func($work, $bulk);
        return $this->client()->getManager()->executeBulkWrite($this->schema . '.' . $what, $bulk);
    }

    public function insertMany2($what, array $values)
    {
        return $this->bulk($what, function (BulkWrite $bulk) use ($values) {
            foreach ($values as $value) {
                $bulk->insert($value);
            }
        })->getInsertedCount();
    }

    protected function makeOptions(Query $query)
    {
        $options = [];

        if ($query->column) {
            $options['projection'] = $query->column;
            $options['projection'] = array_combine($options['projection'], array_fill(0, count($options['projection']), 1));
        }

        if ($query->offset) {
            $options['skip'] = (int)$query->offset;
        }

        if ($query->limit) {
            $options['limit'] = (int)$query->limit;
        }

        if ($query->order) {
            $options['sort'] = $query->order;

            array_walk($options['sort'], function (&$v) {
                $v = SORT_DESC == $v ? -1 : 1;
            });
        }

//        if (isset($options['projection'])) {
//            $options['projection']['_id'] = 0;
//        } else {
//            $options['projection'] = ['_id' => 0];
//        }

//        $options['maxTimeMS'] = 1;

        return $options;
    }

    public function selectMany($what, Query $query = null)
    {
        // TODO: Implement selectMany() method.
    }

    public function updateMany($what, array $values, Query $query = null)
    {
        // TODO: Implement updateMany() method.
    }

    public function deleteMany($what, Query $query = null)
    {
        // TODO: Implement deleteMany() method.
    }
}