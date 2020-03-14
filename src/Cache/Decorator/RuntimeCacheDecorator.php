<?php

namespace SNOWGIRL_CORE\Cache\Decorator;

use Psr\Log\LoggerInterface;
use SNOWGIRL_CORE\Cache\CacheInterface;

class RuntimeCacheDecorator extends AbstractCacheDecorator
{
    private $runtime;
    private $saved;
    private $found;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(CacheInterface $cache, LoggerInterface $logger)
    {
        parent::__construct($cache);

        $this->runtime = [];
        $this->saved = 0;
        $this->found = 0;
        $this->logger = $logger;
    }

    public function __destruct()
    {
        $this->logger->info('saved=' . $this->saved . ', found=' . $this->found);
    }

    public function set(string $key, $value, int $lifetime = null): bool
    {
        $output = parent::set($key, $value, $lifetime);

        $this->runtime[$key] = $value;
        $this->logger->debug(__FUNCTION__ . ' saved \'' . $key . '\'');
        $this->saved++;

        return $output;
    }

    public function setMulti(array $values, int $lifetime = null): bool
    {
        $output = parent::setMulti($values, $lifetime);

        $this->runtime = array_merge($this->runtime, $values);
        $this->logger->debug(__FUNCTION__ . ' saved \'' . implode(', ', array_keys($values)) . '\'');
        $this->saved += count($values);

        return $output;
    }

    public function has(string $key, &$value = null): bool
    {
        if (array_key_exists($key, $this->runtime)) {
            $this->logger->debug(__FUNCTION__ . ' found \'' . $key . '\'');
            $value = $this->runtime[$key];
            $this->found++;

            return true;
        }

        $output = parent::has($key, $value);

        if ($output) {
            $this->runtime[$key] = $value;
            $this->logger->debug(__FUNCTION__ . ' saved \'' . $key . '\'');
            $this->saved++;

            return true;
        }

        return false;
    }

    public function getMulti(array $keys): array
    {
        $found = array_filter($keys, function ($key) {
            return array_key_exists($key, $this->runtime);
        });

        if ($found) {
            $this->logger->debug(__FUNCTION__ . ' found \'' . implode(', ', $found) . '\'');
            $check = array_diff($keys, $found);
            $this->found += count($found);
        } else {
            $check = $keys;
        }

        $output = parent::getMulti($check);

        $this->runtime = array_merge($this->runtime, $output);
        $this->logger->debug(__FUNCTION__ . ' saved \'' . implode(', ', array_keys($output)) . '\'');
        $this->saved += count($output);

        foreach ($found as $key) {
            $output[$key] = $this->runtime[$key];
        }

        return $output;
    }

    public function delete(string $key): bool
    {
        $output = parent::delete($key);

        if (array_key_exists($key, $this->runtime)) {
            unset($this->runtime[$key]);
        }

        return $output;
    }

    public function flush(): bool
    {
        $output = parent::flush();
        $this->runtime = [];

        return $output;
    }
}