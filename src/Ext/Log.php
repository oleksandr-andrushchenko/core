<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 11/9/17
 * Time: 3:30 PM
 */
namespace SNOWGIRL_CORE\Ext;

use SNOWGIRL_CORE\Service\Logger;

/**
 * Class Log
 * @package SNOWGIRL_CORE\Ext
 */
trait Log
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

