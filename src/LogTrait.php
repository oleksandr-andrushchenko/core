<?php

namespace SNOWGIRL_CORE;

use SNOWGIRL_CORE\Service\Logger;

trait LogTrait
{
    /** @var Logger */
    protected $logger;

    protected function log($msg, $type = Logger::TYPE_DEBUG, $raw = false)
    {
        $this->logger->make($msg, $type, $raw);
        return $this;
    }

    protected function logException(\Exception $ex, $type = Logger::TYPE_ERROR)
    {
        $this->logger->makeException($ex, $type);
        return $this;
    }
}

