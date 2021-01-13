<?php

namespace SNOWGIRL_CORE\Memcache;

use SNOWGIRL_CORE\AbstractApp;
use Throwable;

class MemcacheDynamicPrefixResolver
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

        try {
            if (!$this->prefix = file_get_contents($this->getFileName())) {
                $this->prefix = $this->genNewPrefix();
            }
        } catch (Throwable $e) {
            $this->app->container->logger->error($e);
            $this->prefix = date('Y-m-d');
        }

        return $this->prefix;
    }

    /**
     * @return string
     */
    public function genNewPrefix(): string
    {
        $this->prefix = time();

        try {
            file_put_contents($this->getFileName(), $this->prefix);
        } catch (Throwable $e) {
            $this->app->container->logger->error($e);
            $this->prefix = date('Y-m-d');
        }

        return $this->prefix;
    }

    /**
     * @return string
     */
    private function getFileName(): string
    {
        return $this->app->getServerDir($this->app->configMasterOrOwn('app.tmp_dir', '@root/var/tmp')) . '/memcache_prefix_key.txt';
        return $this->app->dirs['@tmp'] . '/memcache_prefix_key.txt';
    }
}
