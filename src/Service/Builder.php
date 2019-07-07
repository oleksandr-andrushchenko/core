<?php

namespace SNOWGIRL_CORE\Service;

use SNOWGIRL_CORE\Service\Storage\Builder as Storage;

/**
 * Class Builder
 *
 * @property Rdbms     rdbms
 * @method Rdbms rdbms($provider = null, $key = null, $master = false)
 * @property Ftdbms    ftdbms
 * @method  Ftdbms ftdbms($provider = null, $key = null, $master = false)
 * @property Nosql     nosql
 * @method Nosql nosql($provider = null, $key = null, $master = false)
 * @property Logger    logger
 * @method  Logger logger($provider = null, $key = null, $master = false)
 * @property Mcms      mcms
 * @method  Mcms mcms($provider = null, $key = null, $master = false)
 * @property Dcms      dcms
 * @method  Dcms dcms($provider = null, $key = null, $master = false)
 * @property Transport transport
 * @method  Transport transport($provider = null, $key = null, $master = false)
 * @property Profiler  profiler
 * @method  Profiler profiler($provider = null, $key = null, $master = false)
 * @package SNOWGIRL_CORE\Service
 */
class Builder extends \SNOWGIRL_CORE\Builder
{
    protected function _get($k)
    {
        switch ($k) {
            case Storage::TYPE_RDBMS:
//            case Storage::TYPE_NOSQL:
            case 'logger':
            case 'mcms':
            case 'transport':
            case 'dcms':
            case 'profiler':
            case Storage::TYPE_FTDBMS:
                return $this->get($k);
            default:
                return parent::_get($k);
        }
    }

    protected function _call($fn, array $args)
    {
        switch ($fn) {
            case Storage::TYPE_RDBMS:
            case Storage::TYPE_FTDBMS:
//            case Storage::TYPE_NOSQL:
            case 'logger':
            case 'mcms':
            case 'transport':
            case 'dcms':
            case 'profiler':
                return $this->get($fn, $args[0] ?? null, $args[1] ?? null, $args[2] ?? false);
            default:
                return parent::_call($fn, $args);
        }
    }

    protected $instances = [];

    protected function get($service, $provider = null, $key = null, $master = false)
    {
        if ($master && $this->app->configMaster) {
            $config = $this->app->configMaster;
            $master = true;
        } else {
            $config = $this->app->config;
            $master = false;
        }

        $provider = $provider ?: $config->services->$service;
        $key = $key ?: 'default';

        //@todo remove when support is off
        if (in_array($service, [
            Storage::TYPE_RDBMS,
            Storage::TYPE_FTDBMS,
//            Storage::TYPE_NOSQL
        ])) {
            return $this->app->storage->$provider($key, $master);
        }

        $class = implode('_', [
            $service,
            $provider,
            $key,
            $master ? 'ms' : 'sf'
        ]);

        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        }

        /** @var \SNOWGIRL_CORE\Service $instance */
        $instance = $this->app->getObject(
            'Service\\' . ucfirst($service) . '\\' . ucfirst($provider),
            $config->{$service . '.' . $provider . '.' . $key}([]),
            $this->app
        );

        $instance->setServiceName(($master ? 'ms:' : '') . $service)
            ->setProviderName($provider);

        $this->instances[$class] = $instance;

        return $this->instances[$class];
    }
}