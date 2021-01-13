<?php

namespace SNOWGIRL_CORE\Memcache;

class MemcacheDisableSetOperationDecorator extends MemcacheAbstractDecorator
{
    public function set(string $key, $value, int $lifetime = null): bool
    {
        return false;
    }

    public function setMulti(array $values, int $lifetime = null): bool
    {
        return false;
    }
}