<?php

namespace SNOWGIRL_CORE\Cache;

use Psr\Log\LoggerInterface;
use Memcached;

class MemCache implements CacheInterface
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
    private $persistentPrefix;

    /**
     * @var DynamicPrefixResolver
     */
    private $dynamicPrefixResolver;

    /**
     * @var int
     */
    private $weight;

    /**
     * @var int - in seconds
     */
    private $lifetime;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Memcached
     */
    private $memcache;

    public function __construct(
        string $host,
        int $port,
        string $persistentPrefix,
        DynamicPrefixResolver $dynamicPrefixResolver,
        int $weight,
        int $lifetime,
        LoggerInterface $logger
    )
    {
        $this->host = $host;
        $this->port = $port;
        $this->persistentPrefix = $persistentPrefix;
        $this->dynamicPrefixResolver = $dynamicPrefixResolver;
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
     */
    public function flush(): bool
    {
        $this->dynamicPrefixResolver->setNewPrefix();
        $output = true;
//        $output = $this->getClient()->flush();

        $this->logger->debug(__FUNCTION__, [
            'result_code' => $this->getClient()->getResultCode(),
            'output' => $output,
        ]);

        return $output;
    }

    /**
     * @return Memcached
     */
    private function getClient(): Memcached
    {
        if (null === $this->memcache) {
            $this->logger->debug(__FUNCTION__);

            $this->memcache = new Memcached($this->persistentPrefix);

            $prefixKey = $this->persistentPrefix . $this->dynamicPrefixResolver->getPrefix();

            $this->memcache->setOption(Memcached::OPT_PREFIX_KEY, $prefixKey);
            $this->memcache->setOption(Memcached::OPT_TCP_NODELAY, true);
            $this->memcache->setOption(Memcached::OPT_NO_BLOCK, true);
            $this->memcache->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);
            $this->memcache->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
            $this->memcache->setOption(Memcached::OPT_BUFFER_WRITES, true);
            $this->memcache->setOption(Memcached::GET_PRESERVE_ORDER, true);

            if (!count($this->memcache->getServerList())) {
                $this->memcache->addServer($this->host, $this->port, $this->weight);
            }
        }

        return $this->memcache;
    }
}