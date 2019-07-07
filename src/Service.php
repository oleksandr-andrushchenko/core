<?php

namespace SNOWGIRL_CORE;

use SNOWGIRL_CORE\Service\LogTrait;

abstract class Service extends Configurable
{
    use LogTrait;

    public function __construct($config, App $app = null)
    {
        $this->initialize2($app);
        parent::__construct($config);
    }

    protected function initialize2(App $app = null)
    {
    }
}