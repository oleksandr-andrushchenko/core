<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 3/6/18
 * Time: 6:20 PM
 */
namespace SNOWGIRL_CORE;

/**
 * @todo...
 * Class FS
 * @package SNOWGIRL_CORE
 */
class FS
{
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }
}