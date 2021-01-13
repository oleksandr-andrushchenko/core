<?php

namespace SNOWGIRL_CORE;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SwiftMailerHandler;
use Monolog\Logger;

use SNOWGIRL_CORE\Console\ConsoleApp;
use SNOWGIRL_CORE\Elasticsearch\ElasticsearchInterface;
use SNOWGIRL_CORE\Http\HttpApp;

use Psr\Log\LoggerInterface;

use SNOWGIRL_CORE\Memcache\MemcacheInterface;
use SNOWGIRL_CORE\Memcache\MemcacheDynamicPrefixResolver;
use SNOWGIRL_CORE\Memcache\MemcacheNullDecorator;
use SNOWGIRL_CORE\Memcache\MemcacheDebuggerDecorator;
use SNOWGIRL_CORE\Memcache\MemcacheRuntimeDecorator;
use SNOWGIRL_CORE\Memcache\Memcache;

use SNOWGIRL_CORE\Mysql\Mysql;
use SNOWGIRL_CORE\Mysql\MysqlDebuggerDecorator;

use SNOWGIRL_CORE\Elasticsearch\Elasticsearch;
use SNOWGIRL_CORE\Elasticsearch\ElasticsearchDebuggerDecorator;

use SNOWGIRL_CORE\Logger\NullLogger;
use SNOWGIRL_CORE\Mailer\MailerInterface;
use SNOWGIRL_CORE\Mailer\NullMailer;
use SNOWGIRL_CORE\Mailer\Decorator\DebuggerMailerDecorator;
use SNOWGIRL_CORE\Mailer\SwiftMailer;

use SNOWGIRL_CORE\Helper\Arrays;
use SNOWGIRL_CORE\Mysql\MysqlInterface;

/**
 * Class Container
 * @property AbstractApp|HttpApp|ConsoleApp app
 * @property LoggerInterface|Logger logger
 * @method  Logger logger(bool $master = false)
 * @property MysqlInterface mysql
 * @method MysqlInterface mysql(bool $master = false)
 * @property ElasticsearchInterface elasticsearch
 * @method ElasticsearchInterface elasticsearch(bool $master = false)
 * @property MemcacheInterface memcache
 * @method MemcacheInterface memcache(bool $master = false)
 * @property MailerInterface|SwiftMailer mailer
 * @method MailerInterface|SwiftMailer mailer(bool $master = false)
 * @package SNOWGIRL_CORE
 */
class Container
{
    /**
     * @var AbstractApp
     */
    private $app;

    private $definitions;
    private $singletons;

    public function __construct(AbstractApp $app)
    {
        $this->app = $app;
        $this->definitions = $this->definitions();
        $this->singletons = [];
    }

    public function __get($k)
    {
        return $this->$k = $this->get($k);
    }

    public function __call($fn, array $args)
    {
        return $this->call($fn, $args);
    }

    public function __isset($name)
    {
        return property_exists($this, $name);
    }

    public function makeSingle(string $name, string $configKey = null, array $config = [], bool $master = false)
    {
        $master = $master && $this->app->configMaster;

        $key = implode('_', [$name, $master ? 'ms' : 'sf']);

        if (!empty($this->singletons[$key])) {
            return $this->singletons[$key];
        }

        $instance = $this->definitions[$name](array_merge(
            Arrays::getValue($master ? $this->app->configMaster : $this->app->config, $configKey ?? $name, []),
            ['master' => $master],
            $config
        ));

        $this->singletons[$key] = $instance;

        return $instance;
    }

    private function get($k)
    {
        if (array_key_exists($k, $this->definitions)) {
            return $this->makeSingle($k);
        }

        return null;
    }

    private function call($fn, array $args)
    {
        if (array_key_exists($fn, $this->definitions)) {
            return $this->makeSingle($fn, null, [], $args[0] ?? false);
        }

        return null;
    }

    private function makeLogger(string $name, bool $master = false): Logger
    {
        /** @var Logger $logger */
        $logger = $this->makeSingle('logger');

        return $logger->withName(($master ? 'ms:' : '') . $name);
    }

    public function getObject(string $class)
    {
        $class = $this->findClass($class, $found);

        if (!$found) {
            if (!class_exists($class)) {
                return false;
            }
        }

        $params = func_get_args();
        array_shift($params);

        return new $class(...$params);
    }

    public function findClass(string $class, &$found = false)
    {
        $stack = array_values($this->app->namespaces);

        for ($k = 0, $last = count($stack) - 1; $k < $last; $k++) {
            $tmp = $stack[$k] . '\\' . $class;

            if ($this->app->loader->findFile($tmp)) {
                $found = true;
                return $tmp;
            }
        }

        return $stack[$last] . '\\' . $class;
    }

    public function updateDefinition(string $name, array $newConfig, callable $newDefinition = null): self
    {
        if (empty($this->definitions[$name])) {
            return $this;
        }

        $oldDefinition = $this->definitions[$name];

        $this->definitions[$name] = function (array $config) use ($oldDefinition, $newConfig, $newDefinition) {
            $config = array_merge($config, $newConfig);

            if (null === $newDefinition) {
                return $oldDefinition($config);
            }

            return $newDefinition($oldDefinition($config), !empty($config['master']));
        };

        if (!empty($this->singletons[$name])) {
            $this->singletons[$name] = null;

            $this->logger->warning('singleton \'' . $name . '\' purged');
        }

        $this->logger->debug('definition \'' . $name . '\' replaced');

        return $this;
    }

    private function definitions(): array
    {
        return [
            'logger_handler_formatter' => function (array $config) {
                return new LineFormatter(
                    empty($config['format']) ? (LineFormatter::SIMPLE_FORMAT . "\n") : ($config['format'] . "\n\n"),
                    null,
                    false,
                    true
                );
            },
            'base_logger' => function (array $config) {
                $isHttp = $this->app instanceof HttpApp;

                if ($isHttp && $this->app->request->isAdminIp()) {
                    $config['enabled'] = true;
                    $config['level'] = Logger::DEBUG;
                }

                if (empty($config['enabled'])) {
                    return new NullLogger();
                }

                $logger = new Logger($this->app->type);

                if ($isHttp) {
                    $logger->pushProcessor(function ($data) {
                        $data['extra']['ip'] = $this->app->request->getClientIp();
//                        $data['extra']['method'] = $this->app->request->getMethod();
//                        $data['extra']['uri'] = $this->app->request->getServer('REQUEST_URI');

                        return $data;
                    });
                }

                /** @var FormatterInterface $formatter */
                $formatter = $this->makeSingle('logger_handler_formatter', 'logger');

                $handler = new StreamHandler($config['stream'], $config['level'] ?? Logger::ERROR);
                $handler->setFormatter($formatter);
                $logger->pushHandler($handler);

                return $logger;
            },
            'logger' => function () {
                /** @var Logger $logger */
                $logger = $this->makeSingle('base_logger', 'logger');
                /** @var FormatterInterface $formatter */
                $formatter = $this->makeSingle('logger_handler_formatter', 'logger');

                if ($this->mailer instanceof SwiftMailer) {
                    $handler = new SwiftMailerHandler(
                        $this->mailer->getClient(),
                        $this->mailer->createNotifyMessage('', ''),
                        Logger::ERROR
                    );
                    $handler->setFormatter($formatter);
                    $logger->pushHandler($handler);
                }

                return $logger;
            },
            'mysql' => function (array $config) {
                $logger = $this->makeLogger('mysql', !empty($config['master']));

                $mysql = new Mysql($config['host'], $config['port'], $config['schema'], $config['user'], $config['password'], $config['socket'], $logger);

                if (!empty($config['debug'])) {
                    $mysql = new MysqlDebuggerDecorator($mysql, $logger->withName('mysql.debugger'));
                }

                return $mysql;
            },
            'elasticsearch' => function (array $config) {
                $logger = $this->makeLogger('elasticsearch', !empty($config['master']));

                $elasticsearch = new Elasticsearch($config['host'], $config['port'], $config['prefix'], $logger);

                if (!empty($config['debug'])) {
                    $elasticsearch = new ElasticsearchDebuggerDecorator($elasticsearch, $logger->withName('elasticsearch.debugger'));
                }

                return $elasticsearch;
            },
            'memcache' => function (array $config) {
                $logger = $this->makeLogger('memcache', !empty($config['master']));

                $dynamicPrefixResolver = new MemcacheDynamicPrefixResolver($this->app);

                $memcache = new Memcache(
                    $config['host'],
                    $config['port'],
                    $config['prefix'],
                    $dynamicPrefixResolver,
                    $config['weight'],
                    $config['lifetime'],
                    $logger);

                if (empty($config['enabled'])) {
                    return new MemcacheNullDecorator($memcache);
                }

                if (!empty($config['runtime'])) {
                    $memcache = new MemcacheRuntimeDecorator($memcache, $logger->withName('memcache.runtime'));
                }

                if (!empty($config['debug'])) {
                    $memcache = new MemcacheDebuggerDecorator($memcache, $logger->withName('memcache.debugger'));
                }

                return $memcache;
            },
            'mailer' => function (array $config) {
                if (empty($config['enabled'])) {
                    return new NullMailer();
                }

                /** @var Logger $logger */
                $logger = $this->makeSingle('base_logger', 'logger', [], !empty($config['master']));
                $logger = $logger->withName('mailer');

                $mailer = new SwiftMailer($config['sender'], $config['host'], $config['port'], $config['encryption'], $config['username'], $config['password'], $config['notifiers'], $logger);

                if (!empty($config['debug'])) {
                    $mailer = new DebuggerMailerDecorator($mailer, $logger->withName('mailer.debugger'));
                }

                return $mailer;
            },
        ];
    }
}