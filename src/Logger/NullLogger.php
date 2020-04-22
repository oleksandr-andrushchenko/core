<?php

namespace SNOWGIRL_CORE\Logger;

use Monolog\Handler\HandlerInterface;
use Monolog\Logger;

class NullLogger extends Logger
{
    public function __construct()
    {
        parent::__construct('');
    }

    public function pushHandler(HandlerInterface $handler)
    {
        return $this;
    }

    public function pushProcessor($callback)
    {
        return $this;
    }

    public function addRecord($level, $message, array $context = array())
    {
        return true;
    }

    public function emergency($message, array $context = array())
    {

    }

    public function alert($message, array $context = array())
    {

    }

    public function critical($message, array $context = array())
    {

    }

    public function error($message, array $context = array())
    {

    }

    public function warning($message, array $context = array())
    {

    }

    public function notice($message, array $context = array())
    {

    }

    public function info($message, array $context = array())
    {
    }

    public function debug($message, array $context = array())
    {

    }

    public function log($level, $message, array $context = array())
    {

    }
}
