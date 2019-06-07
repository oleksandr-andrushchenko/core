<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 4/4/19
 * Time: 10:42 PM
 */

namespace SNOWGIRL_CORE\Manager;

use SNOWGIRL_CORE\Manager;

/**
 * Class DataProvider
 *
 * @package SNOWGIRL_CORE\Manager
 */
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

    abstract public function getListByQuery(string $query, bool $prefix = false): array;

    abstract public function getCountByQuery(string $query, bool $prefix = false): int;
}