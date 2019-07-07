<?php

namespace SNOWGIRL_CORE;

use SNOWGIRL_CORE\Service\Logger;

abstract class Util
{
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->initialize();
    }

    protected function initialize()
    {
        return $this;
    }

    protected function output($text, $type = Logger::TYPE_DEBUG)
    {
        $text = is_array($text) ? implode(PHP_EOL, $text) : $text;

        echo PHP_EOL;
        echo $text;
        echo PHP_EOL;
        $this->app->services->logger->make($text, $type);
        return true;
    }
}