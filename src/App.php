<?php

namespace SNOWGIRL_CORE;

use SNOWGIRL_CORE\Helper\Arrays;
use SNOWGIRL_CORE\Service\Logger;
use SNOWGIRL_CORE\Service\Transport;
use Composer\Autoload\ClassLoader;
use SNOWGIRL_CORE\View\Builder as Views;
use SNOWGIRL_CORE\Manager\Builder as Managers;
use SNOWGIRL_CORE\Util\Builder as Utils;
use SNOWGIRL_CORE\Service\Builder as Services;
use SNOWGIRL_CORE\Service\Storage\Builder as Storage;
use Throwable;
use Exception;
use Closure;

/**
 * Class App
 *
 * @package SNOWGIRL_CORE
 * @property Request request
 * @property Response response
 * @property Router router
 * @property Translator trans
 * @property Geo geo
 * @property SEO seo
 * @property Analytics analytics
 * @property Ads ads
 * @property Views views
 * @property Images images
 * @property Managers managers
 * @property Utils utils
 * @property Tests tests
 * @property Storage storage
 * @property Services services
 * @property RBAC rbac
 */
abstract class App
{
    /** @var App */
    public static $instance;

    public $dirs;
    public $namespaces;

    /** @var ClassLoader */
    public $loader;

    /** @var Config */
    public $config;

    /** @var Config */
    public $configMaster;

    protected $startDt;
    protected $isDev;

    private function __construct(ClassLoader $loader)
    {
        $this->startDt = new DateTime();
        $this->addMaps($loader->getPrefixesPsr4()[__NAMESPACE__ . '\\'][0] . '/../../../..');
        $this->loader = $loader;
        $this->config = $this->getConfig('config.ini');

        if ($master = $this->config->app->master) {
            $this->configMaster = $this->getConfig('../' . $master . '/config.ini');
        }

        $this->dirs['@tmp'] = $this->getServerDir($this->config->app->tmp_dir('@root/var/tmp'));

        $this->logDt();
    }

    protected function getConfig($file): Config
    {
//        parse_ini_string(file_get_contents(), true)
        $file = $this->getServerDir($this->dirs['@root'] . '/' . $file);

        return new Config(parse_ini_string($this->getServerDir(file_get_contents($file)), true));
    }

    public static function getInstance(ClassLoader $loader)
    {
        if (null === static::$instance) {
            date_default_timezone_set('Europe/Kiev');
            ini_set('log_errors', 'On');
            $app = new static($loader);
            static::$instance = $app;
        }

        return static::$instance;
    }

    public function __get($k)
    {
        switch ($k) {
            case 'services':
                return $this->$k = $this->getObject('Service\Builder', $this);
            case 'storage':
                return $this->$k = $this->getObject('Service\Storage\Builder', $this);
            case 'request':
                return $this->$k = $this->getObject('Request', $this);
            case 'response':
                return $this->$k = $this->getObject('Response');
            case 'router':
                return $this->$k = $this->getRouterObject();
            case 'geo':
                return $this->$k = $this->getObject('Geo', $this);
            case 'seo':
                return $this->$k = $this->getObject('SEO', $this);
            case 'views':
                return $this->$k = $this->getObject('View\Builder', $this);
            case 'images':
                return $this->$k = new Images($this);
            case 'managers':
                return $this->$k = $this->getObject('Manager\Builder', $this);
            case 'trans':
                return $this->$k = $this->getObject('Translator', $this)->setLocale($this->request->getClient()->getLocale());
            case 'analytics':
                return $this->$k = $this->getObject('Analytics', $this);
            case 'ads':
                return $this->$k = new Ads($this);
            case 'utils':
                return $this->$k = $this->getObject('Util\Builder', $this);
            case 'rbac':
                return $this->$k = $this->getObject('RBAC', $this);
            case 'tests':
                return $this->$k = $this->getObject('Tests', $this);
            default:
                return $this->$k = null;
        }
    }

    abstract public function run();

    /**
     * @param $k
     *
     * @return App
     */
    public function destroy($k)
    {
        unset($this->$k);
        return $this;
    }

    protected function logError(array &$error, string $handler)
    {
        try {
            $uri = $this->request->getServer('REQUEST_URI');
        } catch (Throwable $ex) {
            $uri = null;
        }

        $error['ex'] = new Exception($error['message'], $error['type']);

        $trace = explode("\n", $error['ex']->getTraceAsString());
        array_shift($trace);
        array_shift($trace);
        array_pop($trace);
        $trace = implode("\n", $trace);

        if (isset($this->dirs) && isset($this->dirs['@root'])) {
            $trace = str_replace($this->dirs['@root'], '@root', $trace);
        }

        $this->services->logger->make(implode("\n", [
            '[' . $handler . '_handler] on ' . $uri,
            '[' . $error['type'] . '] ' . $error['message'] . ' in ' . $error['file'] . '(' . $error['line'] . ')',
            $trace
        ]), Logger::TYPE_ERROR)->makeEmpty()->makeEmpty();

        return $this;
    }

    public function setErrorHandler(Closure $fn = null)
    {
        set_error_handler(function ($num, $str, $file, $line) use ($fn) {
            $error = [
                'type' => $num,
                'message' => $str,
                'file' => $file,
                'line' => $line,
            ];

            if (in_array($error['type'], $this->config->app->throw_exception_on([E_ERROR]))) {
                throw new Exception($error['message'], $error['type']);
            }

            $this->logError($error, 'error');

            $fn && $fn($error, $this);

            return true;
        });

        return $this;
    }

    public function setExceptionHandler(Closure $fn = null)
    {
        set_exception_handler(function (Throwable $ex) use ($fn) {
            $error = [
                'type' => $ex->getCode(),
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
            ];

            $this->logError($error, 'exception');

            $fn && $fn($error, $this);
        });

        return $this;
    }

    public function setShutdownHandler(Closure $fn = null)
    {
        register_shutdown_function(function () use ($fn) {
            if (!$error = error_get_last()) {
                return true;
            }

            $this->logError($error, 'shutdown');

            $fn && $fn($error, $this);

            return true;
        });

        return $this;
    }

    public function logRequest()
    {
        $this->services->logger->make(implode(' ', [
            '[' . $this->request->getMethod() . ']',
            '[client=' . ($this->request->getClient()->isLoggedIn() ? $this->request->getClient()->getUser()->getId() : '') . ']',
            $this->request->getServer('REQUEST_URI')
        ]));
    }

    public function __destruct()
    {
        $this->logDt(true);
    }

    /**
     * @todo cache results... (important)
     *
     * @param $rawClass
     *
     * @return mixed
     */
    public function getObject($rawClass)
    {
        $class = $this->findClass($rawClass, $found);

        if (!$found) {
            if (!class_exists($class)) {
                return false;
            }
        }

        $params = func_get_args();
        array_shift($params);
        return new $class(...$params);
    }

    public function findClass($rawClass, &$found = false)
    {
        $tmp = 'APP\\' . $rawClass;

        if ($this->loader->findFile($tmp)) {
            $found = true;
            return $tmp;
        }

        return 'SNOWGIRL_CORE\\' . $rawClass;
    }

    public function getServerDir($pathWithAliases)
    {
        return str_replace(array_keys($this->dirs), $this->dirs, $pathWithAliases);
    }

    public function getNotifiers(Transport $transport = null)
    {
        $class = explode('\\', get_class($transport ?: $this->services->transport));
        $class = end($class);

        return $this->config->app->{'notify_' . strtolower($class)}([]);
    }

    public function isDev()
    {
        return $this->isDev ?? $this->isDev = $this->config->app->dev(false);
    }

    public function getSite($default = 'Unknown Site Name')
    {
        return $this->config->site->name($default);
    }

    protected function runAction()
    {
        $controller = $this->request->getController();
        $action = $this->request->getAction();

        $action = implode('', array_map('ucfirst', explode('-', $action)));

        if (!$class = $this->getObject('Controller\\' . ucfirst($controller) . '\\' . $action . 'Action')) {
            $class = $this->getObject('Controller\\' . ucfirst($controller) . '\\' . 'DefaultAction');
        }

        $action = new $class;

        return $action($this);
    }

    /**
     * @param Router $router
     *
     * @return $this
     */
    protected function addRoutes(Router $router)
    {
        false && $router;
        return $this;
    }

    /**
     * For links builds
     *
     * @param Router $router
     *
     * @return $this
     */
    protected function addFakeRoutes(Router $router)
    {
        false && $router;
        return $this;
    }

    protected function getRouterObject()
    {
        /** @var Router $router */
        $router = $this->getObject('Router', $this);

        $router->addRoute('index', new Route('/', [
            'controller' => 'outer',
            'action' => 'index'
        ]));

        $router->addRoute('image', new Route('img/:format/:param/:file', [
            'controller' => 'image',
            'action' => 'get'
        ]));

        $router->addRoute('admin', new Route('admin/:action', [
            'controller' => 'admin',
            'action' => 'index'
        ]));

        $this->addRoutes($router);

        $router->addRoute('default', new Route(':action', [
            'controller' => 'outer',
            'action' => 'default'
        ]));

        $this->addFakeRoutes($router);

        $router->setDefaultRoute('default');

        return $router;
    }

    protected function onErrorLog()
    {
        if ($this->config->app->notify_error_logs(false)) {
            $this->services->logger->setOnErrorMade(function ($error) {
                try {
                    if ($this->request->getReferer() || $this->request->isCli()) {
                        $this->views->errorLogEmail($error)
                            ->processNotifiers();
                    }
                } catch (Throwable $ex) {
                    if ($this->rbac->hasPerm(RBAC::PERM_SHOW_TRACE)) {
                        dump($ex->getMessage());
                    }
                }
            });
        }

        return $this;
    }

    protected function addMaps($root)
    {
        $root = $this->getAbsolutePath($root);

        $this->dirs = [];
        $this->namespaces = [];

        $this->dirs['@root'] = $root;
        $this->dirs['@public'] = $root . '/public';

        $this->dirs['@app'] = $root;
        $this->namespaces['@app'] = 'APP';

        $this->dirs['@core'] = dirname(__DIR__);
        $this->namespaces['@core'] = __NAMESPACE__;

        return $this;
    }

    function getAbsolutePath($path)
    {
//        return realpath($path);

        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = [];

        foreach ($parts as $part) {
            if ('.' == $part) {
                continue;
            }

            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }

        return DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $absolutes);
    }

    protected function logDt($finished = false)
    {
        $msg = [
            "\r\n",
            'Started:',
            $this->startDt->format('d.m.Y H:i:s')
        ];

        if ($finished) {
            $endDt = new DateTime;
            $msg = array_merge($msg, [
                "\r\n",
                'Request:',
                $this->request->getController() . ':' . $this->request->getAction() . ' ' . implode(' ', Arrays::mapWithKeys($this->request->getParams(), function ($k, $v) {
                    return $k . '=' . var_export($v, true);
                })),
                "\r\n",
                'Finished:',
                $endDt->format('d.m.Y H:i:s'),
                "\r\n",
                'Diff:',
                $this->startDt->diff($endDt)->format('%H:%I:%S')
            ]);
        }

        $this->services->logger->make(implode(' ', $msg))->makeEmpty();
    }

    private function __clone()
    {
    }
}