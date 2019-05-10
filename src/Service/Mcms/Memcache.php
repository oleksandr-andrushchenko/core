<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 02.03.16
 * Time: 22:00 PM
 */
namespace SNOWGIRL_CORE\Service\Mcms;

use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\Service\Logger;
use SNOWGIRL_CORE\Service\Mcms;

/**
 * @todo turn off if no connection
 *
 * telnet localhost 11211
 * > stats items
 * > stats cachedump [slab ID] [number of items, 0 for all items] (stats cachedump 1 0)
 * > get [key]
 * > flush_all
 *
 * Class Memcache
 * @package SNOWGIRL_CORE\Service\Mcms
 * @see http://php.net/manual/en/memcached.construct.php
 * @see http://php.net/manual/en/memcached.constants.php
 */
class Memcache extends Mcms
{
    protected $host = '127.0.0.1';
    protected $port = 11211;
    protected $weight = 1;
    /** @var \Memcached */
    protected $memcache;
    protected $persistentId;

    protected function initialize()
    {
        if ($this->isOn()) {
            $this->connect();
        }
    }

    protected function connect()
    {
        if (null == $this->memcache) {
            if (!extension_loaded('memcached')) {
                $this->log('The memcached extension must be loaded for using this backend !', Logger::TYPE_ERROR);
                $this->disable();
                return false;
            }

            try {
                $this->memcache = new \Memcached($this->persistentId);

                $this->memcache->setOption(\Memcached::OPT_PREFIX_KEY, $this->prefix);
                $this->memcache->setOption(\Memcached::OPT_TCP_NODELAY, true);
                $this->memcache->setOption(\Memcached::OPT_NO_BLOCK, true);
                $this->memcache->setOption(\Memcached::OPT_DISTRIBUTION, \Memcached::DISTRIBUTION_CONSISTENT);
                $this->memcache->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
                $this->memcache->setOption(\Memcached::OPT_BUFFER_WRITES, true);
                $this->memcache->setOption(\Memcached::GET_PRESERVE_ORDER, true);

                if (!count($this->memcache->getServerList())) {
                    $this->memcache->addServer(
                        $this->host,
                        $this->port,
                        $this->weight
                    );
                }
            } catch (\Exception $ex) {
                $this->logException($ex);
                $this->disable();
            }
        }

        return true;
    }

    /**
     * coz of $this->memcache->setOption(\Memcached::OPT_PREFIX_KEY, $this->prefix);
     * @param $id
     * @return mixed
     */
    protected function buildId($id)
    {
        return $id;
    }

    public function setPersistentId($id)
    {
        $this->persistentId = $id;
        return $this;
    }

    protected function _set($id, $data, $tags, $lifetime)
    {
//        $this->connect();

        $result = $this->memcache->set($id, [$data, time(), $lifetime], $lifetime);

        if (false === $result) {
            $rsCode = $this->memcache->getResultCode();
            $rsMsg = $this->memcache->getResultMessage();
            $this->log("Memcached::set() failed: [{$rsCode}] {$rsMsg}", Logger::TYPE_WARN);
        }

        if (count($tags)) {
            $this->log("Memcached failed: tags not supported", Logger::TYPE_WARN);
        }

        return $result;
    }

    protected function _setMulti($idToData, $lifetime)
    {
        array_walk($idToData, function (&$i) use ($lifetime) {
            $i = [$i, time(), $lifetime];
        });

        return array_combine(
            array_keys($idToData),
            array_fill(0, count($idToData), $this->memcache->setMulti($idToData, $lifetime))
        );
    }

    protected function _get($id)
    {
//        $this->connect();

        $tmp = $this->memcache->get($id);

        if (isset($tmp[0])) {
            return $tmp[0];
        }

        return false;
    }

    protected function _getMulti($id)
    {
        // in php7.0 signature was changed... not documented yet...
        if ($tmp = $this->memcache->getMulti($id, \Memcached::GET_PRESERVE_ORDER)) {
            return array_map(function ($i) {
                return is_array($i) && isset($i[0]) ? $i[0] : false;
            }, $tmp);
        }

        return array_combine($id, array_fill(0, count($id), false));
    }

    protected function _setWithTag($id, $tagId, $data)
    {
        throw new Exception(__FUNCTION__ . ' is not available for the ' . __CLASS__);
    }

    protected function _getWithTag($id, $tagId)
    {
        throw new Exception(__FUNCTION__ . ' is not available for the ' . __CLASS__);
    }

    protected function _flushByTag($tagId)
    {
        throw new Exception(__FUNCTION__ . ' is not available for the ' . __CLASS__);
    }

    protected function _delete($id)
    {
//        $this->connect();
        return $this->memcache->delete($id);
    }

    protected function _clean($tags)
    {
        $this->log("Memcached failed: tags not supported", Logger::TYPE_WARN);
    }

    protected function _flush()
    {
//        $this->connect();
        return $this->memcache->flush();
    }

    /**
     * @todo optimize...
     *
     * @param $id
     * @return bool|int
     * @throws Exception
     */
    protected function _test($id)
    {
//        $this->connect();
        $tmp = $this->memcache->get($id);

        if (isset($tmp[0], $tmp[1])) {
            return (int)$tmp[1];
        }

        return false;
    }
}