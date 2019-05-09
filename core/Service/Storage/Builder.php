<?php

namespace SNOWGIRL_CORE\Service\Storage;

use SNOWGIRL_CORE\Service\Ftdbms\Elastic;
use SNOWGIRL_CORE\Service\Ftdbms\Sphinx;
use SNOWGIRL_CORE\Service\Rdbms\Mysql;

/**
 * Class Builder
 *
 * @property Mysql   mysql
 * @method Mysql mysql($key = null, $master = false)
 * @property Sphinx  sphinx
 * @method  Sphinx sphinx($key = null, $master = false)
 * @property Elastic elastic
 * @method  Elastic elastic($key = null, $master = false)
 * @package SNOWGIRL_CORE\Service\Storage
 */
class Builder extends \SNOWGIRL_CORE\Builder
{
    public const TYPE_RDBMS = 'rdbms';
    public const TYPE_FTDBMS = 'ftdbms';
    public const TYPE_NOSQL = 'nosql';

    protected $services = [
        'mysql' => self::TYPE_RDBMS,
        'sphinx' => self::TYPE_FTDBMS,
        'elastic' => self::TYPE_FTDBMS,
        'mongo' => self::TYPE_NOSQL,
    ];

    protected $instances = [];

    protected function _get($k)
    {
        switch ($k) {
            case 'mysql':
            case 'sphinx':
            case 'elastic':
//            case 'mongo':
                return $this->get($k);
            default:
                return parent::_get($k);
        }
    }

    protected function _call($fn, array $args)
    {
        switch ($fn) {
            case 'mysql':
            case 'sphinx':
            case 'elastic':
//            case 'mongo':
                return $this->get($fn, $args[0] ?? null, $args[1] ?? false);
            default:
                return parent::_call($fn, $args);
        }
    }

    protected function get($provider, $key = null, $master = false)
    {
        if ($master && $this->app->configMaster) {
            $config = $this->app->configMaster;
            $master = true;
        } else {
            $config = $this->app->config;
            $master = false;
        }

        $key = $key ?: 'default';

        $class = implode('_', [
            $provider,
            $key,
            $master ? 'ms' : 'sf'
        ]);

        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        }

        $service = $this->services[$provider];

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