<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 11/9/17
 * Time: 3:47 PM
 */

namespace SNOWGIRL_CORE\Service\Funcs;

use SNOWGIRL_CORE\Service\Logger;

/**
 * Class Log
 * @package SNOWGIRL_CORE\Service\Funcs
 */
trait Log
{
    use \SNOWGIRL_CORE\Ext\Log {
        log as protected parentLog;
    }

    protected $serviceName;

    public function setServiceName($name)
    {
        $this->serviceName = $name;
        return $this;
    }

    public function getServiceName()
    {
        if (null === $this->serviceName) {
            $tmp = explode('\\', get_called_class());
            $this->serviceName = $tmp[2];
        }

        return $this->serviceName;
    }

    public function setProviderName($name)
    {
        $this->providerName = $name;
        return $this;
    }

    protected $providerName;

    public function getProviderName()
    {
        if (null === $this->providerName) {
            $tmp = explode('\\', get_called_class());
            $this->providerName = $tmp[3];
        }

        return $this->providerName;
    }

    protected function log($msg, $type = Logger::TYPE_DEBUG, $raw = false)
    {
        $this->parentLog($this->getServiceName() . '[' . $this->getProviderName() . ']: ' . $msg, $type, $raw);
        return $this;
    }
}

