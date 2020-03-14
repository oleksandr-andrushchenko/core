<?php

namespace SNOWGIRL_CORE\Cache\Decorator;

use SNOWGIRL_CORE\Cache\CacheInterface;

class AbstractCacheDecorator implements CacheInterface
{
    /**
     * @var CacheInterface
     */
    private $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function set(string $key, $value, int $lifetime = null): bool
    {
        return $this->cache->set($key, $value, $lifetime);
    }

    public function setMulti(array $values, int $lifetime = null): bool
    {
        return $this->cache->setMulti($values, $lifetime);
    }

    public function has(string $key, &$value = null): bool
    {
        return $this->cache->has($key, $value);
    }

    public function getMulti(array $keys): array
    {
        return $this->cache->getMulti($keys);
    }

    public function delete(string $key): bool
    {
        return $this->cache->delete($key);
    }

    public function flush(): bool
    {
        return $this->cache->flush();
    }
}