<?php

namespace SNOWGIRL_CORE;

use Monolog\Logger;

abstract class Util
{
    protected $app;
    protected $debug;

    public function __construct(AbstractApp $app, bool $debug = null)
    {
        $this->app = $app;
        $this->debug = null === $debug ? $app->isDev() : $debug;

        $this->initialize();
    }

    protected function initialize()
    {
        return $this;
    }

    protected function output($text, $type = Logger::DEBUG)
    {
        $text = is_array($text) ? implode(PHP_EOL, $text) : $text;

        echo PHP_EOL;
        echo $text;
        echo PHP_EOL;
        $this->app->container->logger->addRecord($type, $text);
        return true;
    }
}