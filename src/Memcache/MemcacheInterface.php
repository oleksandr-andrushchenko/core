<?php

namespace SNOWGIRL_CORE\Memcache;

interface MemcacheInterface
{
    public function set(string $key, $value, int $lifetime = null): bool;

    public function setMulti(array $values, int $lifetime = null): bool;

    public function has(string $key, &$value = null): bool;

    public function getMulti(array $keys): array;

    public function delete(string $key): bool;

    public function flush(): bool;
}