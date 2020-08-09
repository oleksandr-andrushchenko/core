<?php

namespace SNOWGIRL_CORE\Cache;

use Psr\Log\LoggerInterface;
use Throwable;
use Memcached;
use SNOWGIRL_CORE\Cache\CacheException as Exception;

class MemCache implements CacheInterface
{
    private $host = '127.0.0.1';
    private $port = 11211;
    private $prefix;
    private $weight = 1;
    private $lifetime = 3600;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Memcached
     */
    private $memcache;

    public function __construct(string $host, int $port, string $prefix, int $weight, int $lifetime, LoggerInterface $logger)
    {
        $this->host = $host;
        $this->port = $port;
        $this->prefix = $prefix;
        $this->weight = $weight;
        $this->lifetime = $lifetime;
        $this->logger = $logger;
    }

    /**
     * @param string $key
     * @param null $value
     * @return bool
     * @throws CacheException
     */
    public function has(string $key, &$value = null): bool
    {
        $value = $this->getClient()->get($key);

        return Memcached::RES_NOTFOUND != $this->getClient()->getResultCode();
    }

    /**
     * @param string $key
     * @param $value
     * @param int|null $lifetime
     * @return bool
     * @throws CacheException
     */
    public function set(string $key, $value, int $lifetime = null): bool
    {
        $expired = time() + ($lifetime ?: $this->lifetime);
        $output = $this->getClient()->set($key, $value, $expired);

        $this->logger->debug(__FUNCTION__, [
            'key' => $key,
            'value' => $value,
            'expired' => $expired,
            'output' => $output,
        ]);

        return $output;
    }

    /**
     * @param array $values
     * @param int|null $lifetime
     * @return bool
     * @throws CacheException
     */
    public function setMulti(array $values, int $lifetime = null): bool
    {
        $expired = time() + ($lifetime ?: $this->lifetime);
        $output = $this->getClient()->setMulti($values, $expired);

        $this->logger->debug(__FUNCTION__, [
            'values' => $values,
            'expired' => $expired,
            'output' => $output,
        ]);

        return $output;
    }

    /**
     * @param array $keys
     * @return array
     * @throws CacheException
     */
    public function getMulti(array $keys): array
    {
        if (0 < count($keys)) {
            $output = $this->getClient()->getMulti($keys);
        } else {
            $output = [];
        }

        $this->logger->debug(__FUNCTION__, [
            'keys' => $keys,
            'output' => $output,
        ]);

        if (false === $output) {
            return [];
        }

        return $output;
    }

    /**
     * @param string $key
     * @return bool
     * @throws CacheException
     */
    public function delete(string $key): bool
    {
        $output = $this->getClient()->delete($key);

        $this->logger->debug(__FUNCTION__, [
            'key' => $key,
            'output' => $output,
        ]);

        return $output;
    }

    /**
     * @return bool
     * @throws CacheException
     */
    public function flush(): bool
    {
        $output = $this->getClient()->flush();

        $this->logger->debug(__FUNCTION__, [
            'result_code' => $this->getClient()->getResultCode(),
            'output' => $output,
        ]);

        return $output;
    }

    /**
     * @return Memcached
     * @throws CacheException
     */
    private function getClient(): Memcached
    {
        if (null === $this->memcache) {
            $this->logger->debug(__FUNCTION__);

            try {
                $this->memcache = new Memcached($this->prefix);

                $this->memcache->setOption(Memcached::OPT_PREFIX_KEY, $this->prefix);
                $this->memcache->setOption(Memcached::OPT_TCP_NODELAY, true);
                $this->memcache->setOption(Memcached::OPT_NO_BLOCK, true);
                $this->memcache->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);
                $this->memcache->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
                $this->memcache->setOption(Memcached::OPT_BUFFER_WRITES, true);
                $this->memcache->setOption(Memcached::GET_PRESERVE_ORDER, true);

                if (!count($this->memcache->getServerList())) {
                    $this->memcache->addServer(
                        $this->host,
                        $this->port,
                        $this->weight
                    );
                }
            } catch (Throwable $e) {
                $this->logger->error($e);

                throw new Exception('Connection error', 0, $e);
            }
        }

        return $this->memcache;
    }
}