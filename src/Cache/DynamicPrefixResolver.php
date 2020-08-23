<?php

namespace SNOWGIRL_CORE\Cache;

use SNOWGIRL_CORE\Manager\Cache;

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
     * @var Cache
     */
    private $manager;

    /**
     * DynamicPrefixResolver constructor.
     * @param Cache $manager
     */
    public function __construct(Cache $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        if ($this->prefix) {
            return $this->prefix;
        }

        if (!$this->prefix = $this->manager->get('memcache_prefix_key')) {
            $this->setNewPrefix();
        }

        return $this->prefix;
    }

    /**
     * @return string
     */
    public function setNewPrefix(): string
    {
        $this->manager->set('memcache_prefix_key', $this->prefix = time());

        return $this->prefix;
    }
}
