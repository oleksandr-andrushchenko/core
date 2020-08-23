<?php

namespace SNOWGIRL_CORE\Cache;

use SNOWGIRL_CORE\AbstractApp;

/**
 * Class DynamicPrefixResolver
 * @package SNOWGIRL_CORE\Cache
 */
class DynamicPrefixResolver
{
    /**
     * @var string
     */
    private $prefix;

    /**
     * @var AbstractApp
     */
    private $app;

    /**
     * DynamicPrefixResolver constructor.
     * @param AbstractApp $app
     */
    public function __construct(AbstractApp $app)
    {
        $this->app = $app;
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        if ($this->prefix) {
            return $this->prefix;
        }

        if (!$this->prefix = file_get_contents($this->getFileName())) {
            $this->setNewPrefix();
        }

        return $this->prefix;
    }

    /**
     * @return string
     */
    public function setNewPrefix(): string
    {
        file_put_contents($this->getFileName(), $this->prefix = time());

        return $this->prefix;
    }

    /**
     * @return string
     */
    private function getFileName(): string
    {
        return $this->app->dirs['@tmp'] . '/memcache_prefix_key.txt';
    }
}
