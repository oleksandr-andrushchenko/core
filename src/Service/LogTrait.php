<?php

namespace SNOWGIRL_CORE\Service;

trait LogTrait
{
    use \SNOWGIRL_CORE\LogTrait {
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

