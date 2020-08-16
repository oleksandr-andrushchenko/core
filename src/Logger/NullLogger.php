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

    public function addRecord($level, $message, array $context = [])
    {
        return true;
    }

    public function emergency($message, array $context = [])
    {

    }

    public function alert($message, array $context = [])
    {

    }

    public function critical($message, array $context = [])
    {

    }

    public function error($message, array $context = [])
    {

    }

    public function warning($message, array $context = [])
    {

    }

    public function notice($message, array $context = [])
    {

    }

    public function info($message, array $context = [])
    {
    }

    public function debug($message, array $context = [])
    {

    }

    public function log($level, $message, array $context = [])
    {

    }
}
