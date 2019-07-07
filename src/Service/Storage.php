<?php

namespace SNOWGIRL_CORE\Service;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\Service;
use SNOWGIRL_CORE\Service\Storage\Query;

abstract class Storage extends Service
{
    use ToggleTrait;

    protected $host;

    public function getHost()
    {
        return $this->host;
    }

    protected $port;

    public function getPort()
    {
        return $this->port;
    }

    public function __destruct()
    {
        $this->dropClient();
    }

    /**
     * @var Logger
     */
    protected $logger;

    protected function initialize2(App $app = null)
    {
        parent::initialize2($app);

        if (null !== $app) {
            $this->setOption('logger', $app->services->logger);
        }
    }

    protected $debug;

    public function debug($debug = true)
    {
        $this->debug = $debug;
        return $this;
    }

    abstract public function insertMany($what, array $values, Query $query = null);

    abstract public function insertOne($what, array $values, Query $query = null);

    public function insert($what, array $values, Query $query = null)
    {
        if (!count($values)) {
            return 0;
        }

        $query = Query::normalize($query);

        if (is_array(array_values($values)[0])) {
            return $this->insertMany($what, $values, $query);
        }

        return $this->insertOne($what, $values, $query);
    }

    /**
     * @param            $what
     * @param Query|null $query
     *
     * @return array
     */
    abstract public function selectMany($what, Query $query = null);

    public function selectOne($what, Query $query = null)
    {
        $query = Query::normalize($query);
        $query->limit = 1;
        return ($tmp = $this->selectMany($what, $query)) ? $tmp[0] : null;
    }

    public function select($what, Query $query = null)
    {
        return $this->selectMany($what, $query);
    }

    /**
     * @param            $what
     * @param array      $values
     * @param Query|null $query
     *
     * @return int
     */
    abstract public function updateMany($what, array $values, Query $query = null);

    public function updateOne($what, array $values, Query $query = null)
    {
        $query = Query::normalize($query);
        $query->limit = 1;
        return $this->updateMany($what, $values, $query);
    }

    public function update($what, array $values, Query $query = null)
    {
        return $this->updateMany($what, $values, $query);
    }

    /**
     * @param            $what
     * @param Query|null $query
     *
     * @return int
     */
    abstract public function deleteMany($what, Query $query = null);

    public function deleteOne($what, Query $query = null)
    {
        $query = Query::normalize($query);
        $query->limit = 1;
        return $this->deleteMany($what, $query);
    }

    public function delete($what, Query $query = null)
    {
        return $this->deleteMany($what, $query);
    }

    protected $client;

    protected function connect()
    {
        if (null === $this->client) {
            $this->log(__FUNCTION__);
            try {
                $this->client = $this->_getClient();
            } catch (\Exception $ex) {
                try {
                    $this->dropClient();
                    $this->client = $this->_getClient();
                } catch (\Exception $ex) {

                }
            }
        }

        return $this;
    }

    public function getClient($force = false)
    {
        if ($force) {
            $this->connect();
        }

        return $this->client;
    }

    public function client()
    {
        return $this->getClient(true);
    }

    abstract protected function _getClient();

    protected function dropClient()
    {
        if (null !== $this->client) {
            $this->log(__FUNCTION__);
            $this->_dropClient();
        }
    }

    abstract protected function _dropClient();

    protected function refreshClient()
    {
        $this->log(__FUNCTION__);
        $this->dropClient();
        $this->connect();
        return $this;
    }

    /**
     * @param \Exception|null $ex
     *
     * @return array
     */
    abstract public function getErrors(\Exception $ex = null);

    /**
     * @param Query $query
     *
     * @return $this|Storage
     */
    abstract protected function _req(Query $query);

    protected $req;

    /**
     * @param string|Query $query
     *
     * @return $this|Storage
     */
    protected function __req($query)
    {
        $query = Query::normalize($query);

        try {
            if ($query->log) {
                $this->log($query);
            }

            $this->req = $this->connect()->_req($query);
        } catch (\Exception $ex) {
            foreach ($this->getErrors($ex) as $error) {
                $this->log($error, Logger::TYPE_ERROR);
            }

            $this->req = $this->refreshClient()->_req($query);
        }

        return $this;
    }

    /**
     * @param string|Query $query
     *
     * @return $this|Storage
     */
    public function req($query)
    {
        if ($this->debug) {
            $s = microtime(true);
            $output = $this->__req($query);
            $this->log('Meta: ' . substr(microtime(true) - $s, 0, 7) . '(' . $this->affectedRows() . ')');
            return $output;
        }

        return $this->__req($query);
    }

    protected function debugIfNeed(\Closure $callback)
    {
        if ($this->debug) {
            $s = microtime(true);
            $output = call_user_func($callback);
            $this->log('Meta: ' . substr(microtime(true) - $s, 0, 7) . '(' . $this->affectedRows() . ')');
            return $output;
        }

        return call_user_func($callback);
    }

    /**
     * @param $query
     *
     * @return $this|Storage
     */
    public function debugReq($query)
    {
        $debug = $this->debug;
        $this->debug = true;
        $output = $this->req($query);
        $this->debug = $debug;
        return $output;
    }

    /**
     * @param            $what
     * @param Query|null $query
     *
     * @return array|int|int[]
     */
    abstract public function selectCount($what, Query $query = null);

    abstract public function foundRows();

    abstract public function affectedRows();

    /**
     * @param null $key
     *
     * @return array[]
     */
    abstract public function reqToArrays($key = null);

    /**
     * @return array
     */
    abstract public function reqToArray();

    /**
     * @param      $className
     * @param null $key
     *
     * @return array
     */
    abstract public function reqToObjects($className, $key = null);

    abstract public function numRows();

    abstract public function insertedId();

    /**
     * @return array
     */
    abstract public function warnings();

    /**
     * @return array
     */
    abstract public function errors();
}