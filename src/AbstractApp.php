<?php

namespace SNOWGIRL_CORE;

use SNOWGIRL_CORE\Console\ConsoleApp;
use SNOWGIRL_CORE\Helper\Arrays;
use Composer\Autoload\ClassLoader;
use SNOWGIRL_CORE\Http\HttpApp;
use SNOWGIRL_CORE\Http\Route;
use SNOWGIRL_CORE\Http\Router;
use SNOWGIRL_CORE\View\Builder as Views;
use SNOWGIRL_CORE\Manager\Builder as Managers;
use SNOWGIRL_CORE\Util\Builder as Utils;
use Throwable;
use Exception;
use Closure;
use DateTime;

/**
 * Class App
 * @package SNOWGIRL_CORE
 * @property AbstractRequest request
 * @property AbstractResponse response
 * @property Translator trans
 * @property Geo geo
 * @property SEO seo
 * @property Views views
 * @property Images images
 * @property Managers managers
 * @property Utils utils
 * @property Tests tests
 * @property RBAC rbac
 * @property Analytics analytics
 * @property Ads ads
 * @property Router router
 */
abstract class AbstractApp
{
    public $dirs;
    public $namespaces;

    /**
     * @var Container
     */
    public $container;

    public $type;

    /**
     * @var ClassLoader
     */
    public $loader;

    /**
     * @var array|bool
     */
    public $config;

    /**
     * @var array|bool
     */
    public $configMaster;

    /**
     * @var DateTime
     */
    private $startDt;

    public function __construct(ClassLoader $loader)
    {
        $this->loader = $loader;
    }

    public function __get(string $k)
    {
        return $this->$k = $this->get($k);
    }

    public function run()
    {
        $this->startDt = new DateTime();
        $this->addMaps($this->loader->getPrefixesPsr4()[__NAMESPACE__ . '\\'][0] . '/../../../..');

        $this->type = $this->getType();
        $this->config = $this->getConfig(
            'config/app.ini',
            'config/' . $this->type . '.ini'
        );

        if ($master = $this->config('app.master', false)) {
            $this->configMaster = $this->getConfig(
                '../' . $master . '/config/app.ini',
                '../' . $master . '/config/' . $this->type . '.ini'
            );
        }

        $this->container = new Container($this);

        $this->dirs['@tmp'] = $this->getServerDir($this->config('app.tmp_dir', '@root/var/tmp'));
        $this->register();

        $this->logDt();
    }

    public function destroy(string $k): AbstractApp
    {
        unset($this->$k);

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

            if (in_array($error['type'], $this->config('app.throw_exception_on', [E_ERROR]))) {
                throw new Exception($error['message'], $error['type']);
            }

            $this->logError($error, 'error');

            $fn && $fn($error, $this);

            return true;
        });

        return $this;
    }

    public function setExceptionHandler(callable $fn = null)
    {
        set_exception_handler(function (Throwable $e) use ($fn) {
            $error = [
                'type' => $e->getCode(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
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

    abstract public function logRequest();

    public function __destruct()
    {
        $this->logDt(true);
    }

    public function getServerDir($pathWithAliases)
    {
        return str_replace(array_keys($this->dirs), $this->dirs, $pathWithAliases);
    }

    public function getSite($default = 'Unknown Site Name')
    {
        return $this->config('site.name', $default);
    }

    public function config(string $key, $default = null)
    {
        return Arrays::getValue($this->config, $key, $default);
    }

    public function configMaster(string $key, $default = null)
    {
        return Arrays::getValue($this->configMaster, $key, $default);
    }

    public function configMasterOrOwn(string $key, $default = null)
    {
        if (null === $this->configMaster) {
            return $this->config($key, $default);
        }

        return $this->configMaster($key, $default);
    }

    public function getAbsolutePath(string $path): string
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

    protected function get(string $k)
    {
        switch ($k) {
            case 'views':
                return $this->container->getObject('View\Builder', $this);
            case 'router':
                return $this->getRouterObject();
            case 'geo':
                return $this->container->getObject('Geo', $this);
            case 'seo':
                return $this->container->getObject('SEO', $this);
            case 'images':
                return new Images($this);
            case 'managers':
                return $this->container->getObject('Manager\Builder', $this);
            case 'trans':
                return $this->container->getObject('Translator', $this)->setLocale('');
            case 'utils':
                return $this->container->getObject('Util\Builder', $this);
            case 'rbac':
                return $this->container->getObject('RBAC', $this);
            case 'tests':
                return $this->container->getObject('Tests', $this);
//            @todo as service
            case 'analytics':
                return $this->container->getObject(
                    'Analytics',
                    !empty($this->config('analytics.enabled', false)),
                    $this->config('analytics.file_template', '@root/var/log/{key}.log'),
                    !empty($this->config('analytics.debug', false)),
                    $this
                );
            case 'ads':
                return new Ads($this);
            default:
                return null;
        }
    }

    protected function addRoutes(Router $router): AbstractApp
    {
        false && $router;

        return $this;
    }

    protected function addFakeRoutes(Router $router): AbstractApp
    {
        false && $router;

        return $this;
    }

    protected function getRouterObject(): Router
    {
        /** @var Router $router */
        $router = $this->container->getObject('Http\Router', $this);

        $router->addRoute('index', new Route('/', [
            'controller' => 'outer',
            'action' => 'index',
        ]));

        $router->addRoute('image', new Route('img/:format/:param/:file', [
            'controller' => 'image',
            'action' => 'get',
        ]));

        $router->addRoute('admin', new Route('admin/:action', [
            'controller' => 'admin',
            'action' => 'index',
        ]));

        $this->addRoutes($router);

        $router->addRoute('default', new Route(':action', [
            'controller' => 'outer',
            'action' => 'default',
        ]));

        $this->addFakeRoutes($router);

        $router->setDefaultRoute('default');

        return $router;
    }

    protected function register()
    {
        Image::setApp($this);
    }

    protected function getConfig(string $file): array
    {
        $output = [];

        foreach (func_get_args() as $file) {
            $file = $this->getServerDir($this->dirs['@root'] . '/' . $file);
            $config = parse_ini_string($this->getServerDir(file_get_contents($file)), true);

            $output = array_replace_recursive($output, $config);
        }

        return $output;
    }

    protected function runAction()
    {
        $controller = $this->request->getController();
        $action = $this->request->getAction();

        $action = implode('', array_map('ucfirst', explode('-', $action)));

        if (!$class = $this->container->getObject('Controller\\' . ucfirst($controller) . '\\' . $action . 'Action')) {
            $class = $this->container->getObject('Controller\\' . ucfirst($controller) . '\\' . 'DefaultAction');
        }

        $action = new $class;

        return $action($this);
    }

    protected function addMaps($root): AbstractApp
    {
        $root = $this->getAbsolutePath($root);

        $this->dirs = [];
        $this->namespaces = [];

        $this->dirs['@root'] = $root;
        $this->dirs['@public'] = $root . DIRECTORY_SEPARATOR . 'public';

        $this->dirs['@app'] = $root;
        $this->namespaces['@app'] = 'APP';

        $this->dirs['@core'] = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');
        $this->namespaces['@core'] = __NAMESPACE__;

        return $this;
    }

    protected function logDt(bool $finished = false)
    {
        $msg = [
            "\r\n",
            'Started:',
            $this->startDt->format('d.m.Y H:i:s'),
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
                $endDt->diff($this->startDt)->format('%H:%I:%S'),
            ]);
        }

        $this->container->logger->debug(implode(' ', $msg));
    }

    protected function logError(array &$error, string $handler)
    {
        $error['ex'] = new Exception($error['message'], $error['type']);

        $trace = explode("\n", $error['ex']->getTraceAsString());
//        array_shift($trace);
//        array_shift($trace);
//        array_pop($trace);
        $trace = implode("\n", $trace);

        if (isset($this->dirs) && isset($this->dirs['@root'])) {
            $trace = str_replace($this->dirs['@root'], '@root', $trace);
        }

        $this->container->logger->error(implode("\n", [
            '[' . $handler . '_handler] on ' . $this->request->getController() . ':' . $this->request->getAction(),
            '[' . $error['type'] . '] ' . $error['message'] . ' in ' . $error['file'] . '(' . $error['line'] . ')',
            $trace,
        ]));

        return $this;
    }

    private function getType(): ?string
    {
        if ($this instanceof ConsoleApp) {
            return 'console';
        }

        if ($this instanceof HttpApp) {
            if (0 === strpos($_SERVER['REQUEST_URI'], '/admin')) {
                return 'admin';
            }

            return 'outer';
        }

        return null;
    }

    private function __clone()
    {
    }
}