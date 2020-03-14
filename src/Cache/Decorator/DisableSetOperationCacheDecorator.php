<?php

namespace SNOWGIRL_CORE\Cache\Decorator;

class DisableSetOperationCacheDecorator extends AbstractCacheDecorator
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