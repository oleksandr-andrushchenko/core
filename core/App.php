<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 11/3/16
 * Time: 3:42 AM
 */

namespace SNOWGIRL_CORE;

use SNOWGIRL_CORE\Exception\HTTP;
use SNOWGIRL_CORE\Exception\HTTP\ServiceUnavailable;
use SNOWGIRL_CORE\Service\Logger;
use SNOWGIRL_CORE\Service\Transport;
use Composer\Autoload\ClassLoader;
use SNOWGIRL_CORE\Exception\HTTP\NotFound;
use SNOWGIRL_CORE\View\Builder as Views;
use SNOWGIRL_CORE\Image\Builder as Images;
use SNOWGIRL_CORE\Manager\Builder as Managers;
use SNOWGIRL_CORE\Util\Builder as Utils;
use SNOWGIRL_CORE\Service\Builder as Services;
use SNOWGIRL_CORE\Service\Storage\Builder as Storage;

//use SNOWGIRL_CORE\FS;

/**
 * Class App
 * @package SNOWGIRL_CORE
 * @property Request request
 * @property Response response
 * @property Router router
 * @property Translator translator
 * @property Geo geo
 * @property SEO seo
 * @property Analytics analytics
 * @property Ads ads
 * @property Views views
 * @property Images images
 * @property Managers managers
 * @property Utils utils
 * @property FS fs
 * @property Tests tests
 * @property Storage storage
 * @property Services services
 */
class App
{
    /** @var App */
    public static $instance;

    public $startTime;

    public $dirs;
    public $namespaces;

    /** @var ClassLoader */
    public $loader;

    /** @var Config */
    public $config;

    /** @var Config */
    public $configMaster;

    private function __construct($root, ClassLoader $loader)
    {
        $this->addMaps($root);
        $this->loader = $loader;
        $this->config = new Config($this->dirs['@root'] . '/config.ini');

        if ($master = $this->config->app->master) {
            $this->configMaster = new Config($this->dirs['@root'] . '/../' . $master . '/config.ini');
        }

        $this->dirs['@tmp'] = $this->getServerDir($this->config->app->tmp('@root/tmp'));
    }

    public static function getInstance(ClassLoader $loader, $root, $ns = null)
    {
        if (null === self::$instance) {
            $time = microtime(true);
            date_default_timezone_set('Europe/Kiev');
            ini_set('log_errors', 'On');
            $app = ($ns ?: 'SNOWGIRL_CORE') . '\\App';
            /** @var App $app */
            $app = new $app($root, $loader);
            $app->startTime = $time;
            self::$instance = $app;
        }

        return self::$instance;
    }

    private function __clone()
    {
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
            case 'translator':
                return $this->$k = $this->getObject('Translator', $this)->setLocale($this->request->getClient()->getLocale());
            case 'analytics':
                return $this->$k = $this->getObject('Analytics', $this);
            case 'ads':
                return $this->$k = new Ads($this);
//            case 'fs':
//                return $this->$k = new FS($this);
            case 'utils':
                return $this->$k = $this->getObject('Util\Builder', $this);
            case 'tests':
                return $this->$k = $this->getObject('Tests', $this);
            default:
                return $this->$k = null;
        }
    }

    /**
     * @param Router $router
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
            'controller' => 'openDoor',
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
            'controller' => 'openDoor',
            'action' => 'default'
        ]));

        $this->addFakeRoutes($router);

        $router->setDefaultRoute('default');

        return $router;
    }

//    protected function runWwwPreParse()
//    {
//
//    }

    protected function onErrorLog()
    {
        if ($this->config->app->notify_error_logs(false)) {
            $this->services->logger->setOnErrorMade(function ($error) {
                try {
                    if ($this->request->getReferer() || $this->request->isCli()) {
                        $this->views->errorLogEmail($error)
                            ->processNotifiers();
                    }
                } catch (\Exception $ex) {
                    if ($this->request->isAdminIp()) {
                        D($ex->getMessage());
                    }
                }
            });
        }

        return $this;
    }

    public function runWww()
    {
        if ($adminIp = $this->request->isAdminIp()) {
            if ($prof = $this->config->app->profiling(false)) {
                $this->services->profiler->enable();
            }

            $this->services->logger
                ->setOption('length', null)
                ->enable();
        }

        $this->services->logger
            ->addParamToLog('IP', $this->request->getClientIp())
            ->setName('web');

        $this->setErrorHandler()
            ->setExceptionHandler()
            ->setShutdownHandler(function (array $error) {
                while (ob_get_level()) {
                    ob_end_clean();
                }

                $this->getResponseWithException(new Exception(implode("\n", $error)))
                    ->send(true);
            })
            ->onErrorLog();

        $host = $this->request->getServer('HTTP_HOST');
        $replace = 'www.';

        if (false !== strpos($host, $replace)) {
            $this->request->redirect(implode('', [
                $this->request->getServer('REQUEST_SCHEME') . '://',
                str_replace($replace, '', $host),
                $this->request->getServer('REQUEST_URI')
            ]), 301);
        }

        $this->logRequest();

        try {
            if ($seconds = $this->config->app->maintenance(false)) {
                if (!$adminIp) {
                    throw (new ServiceUnavailable)->setRetryAfter(max($seconds, 3600));
                }
            }

            $isOk = $this->router->routeCycle($this->request, function () {
                return Controller::run($this);
            });

            if (!$isOk) {
                throw new NotFound;
            }
        } catch (\Exception $ex) {
//            $this->services->logger->makeException($ex, ($ex instanceof NotFound) ? Logger::TYPE_WARN : Logger::TYPE_ERROR);
            $this->services->logger->makeException($ex, Logger::TYPE_ERROR);
            $this->getResponseWithException($ex);
        }

        $this->response->send();
        $this->logPerformance();

        if (isset($prof) && $prof) {
            $this->services->profiler->save();
        }
    }

    /**
     * @throws NotFound
     */
    public function runWwwProfilerPrepend()
    {
        if (!$this->request->isAdminIp()) {
            throw new NotFound;
        }

        $this->services->profiler->prepare();
    }

    public function runCmd($argv)
    {
        $this->setErrorHandler()
            ->setExceptionHandler()
            ->setShutdownHandler();

        $this->services->logger->setName('cmd')->enable();

        $this->onErrorLog()
            ->logRequest();

        $this->request->setController('command');

        array_shift($argv);

        $this->request->setAction(array_shift($argv));

        foreach (array_values($argv) as $k => $v) {
            $this->request->set('param_' . ($k + 1), $v);
        }

        try {
            Controller::run($this);
        } catch (\Exception $ex) {
            $this->services->logger->makeException($ex);
            echo PHP_EOL . implode(PHP_EOL, [
                    get_class($ex),
                    $ex->getMessage(),
                    $ex->getTraceAsString()
                ]);
        }

        $this->logPerformance();
    }

    /**
     * @param \Exception $ex
     * @return Response
     */
    public function getResponseWithException(\Exception $ex)
    {
        if ($ex instanceof HTTP) {
            $code = $ex->getHttpCode();
            $ex->processResponse($this->response);
        } else {
            $code = 500;
        }

        $text = $this->translator->makeText('error.code-' . $code);
        $uri = str_replace(['http://', 'https://'], '', $this->request->getLink(true));

        if ($this->request->isJSON()) {
            return $this->response->setJSON($code, str_replace('{uri}', $uri, $text));
        } elseif ($this->request->isPathFile()) {
            return $this->response->setHTML($code);
        }

        $title = $code;

        if (isset(Response::$codes[$code])) {
            $title .= ' ' . Response::$codes[$code];
        }

        $text = str_replace('{uri}', '<span class="uri">' . $uri . '</span>', $text);

        $view = $this->views->getLayout();
        $view->setError($ex)->setContentByTemplate('error.phtml', [
            'code' => $code,
            'h1' => $title,
            'text' => $text,
            'referer' => $this->request->getReferer(),
            'ex' => $ex,
            'showSuggestions' => !in_array($code, [500, 503]),
            'showTrace' => $this->request->isAdminIp() && $this->request->isAdmin()
        ]);

        return $this->response->setHTML($code, $view);
    }

    /**
     * @param $k
     * @return App
     */
    public function destroy($k)
    {
        unset($this->$k);
        return $this;
    }

    protected function addMaps($root)
    {
        $root = realpath($root);

        $this->dirs = [];
        $this->namespaces = [];

        $this->dirs['@root'] = $root;
        $this->dirs['@web'] = $root . '/web';

        $this->dirs['@app'] = $root . '/app';
        $this->namespaces['@app'] = 'APP';

        $this->dirs['@snowgirl-core'] = dirname(__DIR__);
        $this->namespaces['@snowgirl-core'] = 'SNOWGIRL_CORE';

        return $this;
    }

    public function setErrorHandler()
    {
        set_error_handler(function ($num, $str, $file, $line) {
            $str = implode(' - ', [$str, $file, $line]);

            if ($this->isDev() || in_array($num, $this->config->app->throw_exception_on([E_ERROR]))) {
                throw new Exception($str, $num);
            }

            return true;
        });

        return $this;
    }

    public function setExceptionHandler()
    {
        set_exception_handler(function (\Throwable $ex) {
            $this->services->logger->make(implode(' ', [
                '[exception_handler]',
                '[' . $ex->getCode() . '] ' . $ex->getMessage() . ' in ' . $ex->getFile() . '(' . $ex->getLine() . ')',
                "\n",
                $ex->getTraceAsString()
            ]), Logger::TYPE_ERROR)
                ->makeEmpty()->makeEmpty();
        });

        return $this;
    }

    public function setShutdownHandler(\Closure $fn = null)
    {
        register_shutdown_function(function () use ($fn) {
            if (!$e = error_get_last()) {
                return true;
            }

            try {
                $uri = $this->request->getServer('REQUEST_URI');
            } catch (\Exception $ex) {
                $uri = null;
            }

            $this->services->logger->make(implode(' ', [
                '[shutdown_handler]',
                $uri,
                '[' . $e['type'] . '] ' . $e['message'],
                '[' . $e['line'] . '] ' . $e['file'],
            ]), Logger::TYPE_ERROR)
                ->makeEmpty()->makeEmpty();

            $fn && $fn($e, $this);

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

    public function logPerformance()
    {
        $this->services->logger->make(implode(' ', [
            '[performance]',
            '[' . (microtime(true) - $this->startTime) . ']'
        ]))->makeEmpty();
    }

    /**
     * @todo cache results... (important)
     * @param $rawClass
     * @return mixed
     */
    public function getObject($rawClass)
    {
        $class = $this->findClass($rawClass);
        $params = func_get_args();
        array_shift($params);
        return new $class(...$params);
    }

    public function findClass($rawClass)
    {
        $tmp = 'APP\\' . $rawClass;

        if ($this->loader->findFile($tmp)) {
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

    protected $isDev;

    public function isDev()
    {
        return $this->isDev ?? $this->isDev = $this->config->app->dev(false);
    }

    public function getSite($default = 'Unknown Site Name')
    {
        return $this->config->site->name($default);
    }
}