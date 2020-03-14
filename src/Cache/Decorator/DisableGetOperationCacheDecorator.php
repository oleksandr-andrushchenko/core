<?php

namespace SNOWGIRL_CORE\Cache\Decorator;

class DisableGetOperationCacheDecorator extends AbstractCacheDecorator
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