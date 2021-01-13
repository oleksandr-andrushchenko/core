<?php

namespace SNOWGIRL_CORE\Memcache;

abstract class MemcacheAbstractDecorator implements MemcacheInterface
{
    /**
     * @var MemcacheInterface
     */
    private $memcache;

    public function __construct(MemcacheInterface $memcache)
    {
        $this->memcache = $memcache;
    }

    public function set(string $key, $value, int $lifetime = null): bool
    {
        return $this->memcache->set($key, $value, $lifetime);
    }

    public function setMulti(array $values, int $lifetime = null): bool
    {
        return $this->memcache->setMulti($values, $lifetime);
    }

    public function has(string $key, &$value = null): bool
    {
        return $this->memcache->has($key, $value);
    }

    public function getMulti(array $keys): array
    {
        return $this->memcache->getMulti($keys);
    }

    public function delete(string $key): bool
    {
        return $this->memcache->delete($key);
    }

    public function flush(): bool
    {
        return $this->memcache->flush();
    }
}