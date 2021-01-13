<?php

namespace SNOWGIRL_CORE\Manager;

use SNOWGIRL_CORE\Manager;

abstract class DataProvider
{
    /**
     * @var Manager
     */
    protected $manager;

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    abstract public function getListByQuery(string $term, bool $prefix = false): ?array;

    abstract public function getCountByQuery(string $term, bool $prefix = false): ?int;
}