<?php

namespace SNOWGIRL_CORE\Cache;

class NullCache implements CacheInterface
{
    public function set(string $key, $value, int $lifetime = null): bool
    {
        return false;
    }

    public function setMulti(array $values, int $lifetime = null): bool
    {
        return false;
    }

    public function has(string $key, &$value = null): bool
    {
        return false;
    }

    public function getMulti(array $keys): array
    {
        return [];
    }

    public function delete(string $key): bool
    {
        return false;
    }

    public function flush(): bool
    {
        return false;
    }
}