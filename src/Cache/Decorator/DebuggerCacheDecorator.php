<?php

namespace SNOWGIRL_CORE\Cache\Decorator;

use Psr\Log\LoggerInterface;
use SNOWGIRL_CORE\Cache\CacheInterface;
use SNOWGIRL_CORE\DebuggerTrait;

class DebuggerCacheDecorator extends AbstractCacheDecorator
{
    use DebuggerTrait;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(CacheInterface $cache, LoggerInterface $logger)
    {
        parent::__construct($cache);

        $this->logger = $logger;
    }

    public function set(string $key, $value, int $lifetime = null): bool
    {
        return $this->debug(__FUNCTION__, [$key, $value, $lifetime]);
    }

    public function setMulti(array $values, int $lifetime = null): bool
    {
        return $this->debug(__FUNCTION__, [$values, $lifetime]);
    }

    public function has(string $key, &$value = null): bool
    {
        return $this->debug(__FUNCTION__, [], function () use ($key, &$value) {
            return parent::has($key, $value);
        });
    }

    public function getMulti(array $keys): array
    {
        return $this->debug(__FUNCTION__, func_get_args());
    }

    public function delete(string $key): bool
    {
        return $this->debug(__FUNCTION__, func_get_args());
    }

    public function flush(): bool
    {
        return $this->debug(__FUNCTION__);
    }
}