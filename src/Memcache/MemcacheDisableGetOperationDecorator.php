<?php

namespace SNOWGIRL_CORE\Memcache;

class MemcacheDisableGetOperationDecorator extends MemcacheAbstractDecorator
{
    public function has(string $key, &$value = null): bool
    {
        return false;
    }

    public function getMulti(array $keys): array
    {
        return [];
    }
}