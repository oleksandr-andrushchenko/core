<?php

namespace SNOWGIRL_CORE;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SwiftMailerHandler;
use Monolog\Logger;

use SNOWGIRL_CORE\Cache\CacheInterface;
use SNOWGIRL_CORE\Cache\NullCache;
use SNOWGIRL_CORE\Cache\Decorator\DebuggerCacheDecorator;
use SNOWGIRL_CORE\Cache\Decorator\RuntimeCacheDecorator;
use SNOWGIRL_CORE\Cache\MemCache;

use SNOWGIRL_CORE\Db\DbInterface;
use SNOWGIRL_CORE\Db\NullDb;
use SNOWGIRL_CORE\Db\Decorator\DebuggerDbDecorator;
use SNOWGIRL_CORE\Db\MysqlDb;

use SNOWGIRL_CORE\Http\HttpApp;
use SNOWGIRL_CORE\Indexer\IndexerInterface;
use SNOWGIRL_CORE\Indexer\NullIndexer;
use SNOWGIRL_CORE\Indexer\Decorator\DebuggerIndexerDecorator;
use SNOWGIRL_CORE\Indexer\ElasticIndexer;

use SNOWGIRL_CORE\Mailer\MailerInterface;
use SNOWGIRL_CORE\Mailer\NullMailer;
use SNOWGIRL_CORE\Mailer\Decorator\DebuggerMailerDecorator;
use SNOWGIRL_CORE\Mailer\SwiftMailer;

use SNOWGIRL_CORE\Helper\Arrays;

/**
 * Class Container
 *
 * @property Logger logger
 * @method  Logger logger(bool $master = false)
 * @property DbInterface db
 * @method DbInterface db(bool $master = false)
 * @property IndexerInterface indexer
 * @method IndexerInterface indexer(bool $master = false)
 * @property CacheInterface cache
 * @method CacheInterface cache(bool $master = false)
 * @property MailerInterface mailer
 * @method MailerInterface mailer(bool $master = false)
 *
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
            return $this->makeSingle($fn, $args[0] ?? false);
        }

        return null;
    }

    private function makeSingle(string $name, bool $master = false)
    {
        $master = $master && $this->app->configMaster;

        $key = implode('_', [$name, $master ? 'ms' : 'sf']);

        if (!empty($this->singletons[$key])) {
            return $this->singletons[$key];
        }

        $config = $master ? $this->app->configMaster : $this->app->config;
        $config = Arrays::getValue($config, $name, []);
        $config['master'] = $master;

        $instance = $this->definitions[$name]($config);

        $this->singletons[$key] = $instance;

        return $instance;
    }

    private function makeLogger(string $name, bool $master = false): Logger
    {
        /** @var Logger $logger */
        $logger = $this->makeSingle('logger', false);

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
            'logger' => function (array $config) {
                $logger = new Logger('app');

                if (empty($config['enabled'])) {
                    return $logger->pushHandler(new NullHandler());
                }

                if ($this->app instanceof HttpApp) {
                    $logger->pushProcessor(function ($record) {
                        $record['extra']['ip'] = $this->app->request->getClientIp();
//                        $record['extra']['method'] = $this->app->request->getMethod();
//                        $record['extra']['uri'] = $this->app->request->getServer('REQUEST_URI');

                        return $record;
                    });

                    if ($this->app->request->isAdminIp()) {
                        $config['debug'] = true;
                    }
                }

                if ($this->app->isDev()) {
                    $config['debug'] = true;
                }

                $formatter = new LineFormatter(
                    empty($config['format']) ? (LineFormatter::SIMPLE_FORMAT . "\n") : ($config['format'] . "\n\n"),
                    null,
                    false,
                    true
                );

                $handler = new StreamHandler($config['stream'], empty($config['debug']) ? Logger::INFO : Logger::DEBUG);
                $handler->setFormatter($formatter);
                $logger->pushHandler($handler);

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
            'db' => function (array $config) {
                if (empty($config['enabled'])) {
                    return new NullDb();
                }

                $logger = $this->makeLogger('db', !empty($config['master']));

                $db = new MysqlDb($config['host'], $config['port'], $config['schema'], $config['user'], $config['password'], $config['socket'], $logger);

                if (!empty($config['debug'])) {
                    $db = new DebuggerDbDecorator($db, $logger->withName('db.debugger'));
                }

                return $db;
            },
            'indexer' => function (array $config) {
                if (empty($config['enabled'])) {
                    return new NullIndexer();
                }

                $logger = $this->makeLogger('indexer', !empty($config['master']));

                $indexer = new ElasticIndexer($config['host'], $config['port'], $config['prefix'], $config['service'], $logger);

                if (!empty($config['debug'])) {
                    $indexer = new DebuggerIndexerDecorator($indexer, $logger->withName('indexer.debugger'));
                }

                return $indexer;
            },
            'cache' => function (array $config) {
                if (empty($config['enabled'])) {
                    return new NullCache();
                }

                $logger = $this->makeLogger('cache', !empty($config['master']));

                $cache = new MemCache($config['host'], $config['port'], $config['prefix'], $config['weight'], $config['lifetime'], $logger);

                if (!empty($config['runtime'])) {
                    $cache = new RuntimeCacheDecorator($cache, $logger->withName('cache.runtime'));
                }

                if (!empty($config['debug'])) {
                    $cache = new DebuggerCacheDecorator($cache, $logger->withName('cache.debugger'));
                }

                return $cache;
            },
            'mailer' => function (array $config) {
                if (empty($config['enabled'])) {
                    return new NullMailer();
                }

                $logger = $this->makeLogger('cache', !empty($config['master']));

                $mailer = new SwiftMailer($config['sender'], $config['host'], $config['port'], $config['encryption'], $config['username'], $config['password'], $config['notifiers'], $logger);

                if (!empty($config['debug'])) {
                    $mailer = new DebuggerMailerDecorator($mailer, $logger->withName('mailer.debugger'));
                }

                return $mailer;
            },
        ];
    }
}