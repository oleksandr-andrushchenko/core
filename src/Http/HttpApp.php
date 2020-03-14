<?php

namespace SNOWGIRL_CORE\Http;

use SNOWGIRL_CORE\AbstractApp;
use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\Http\Exception\HttpException;
use SNOWGIRL_CORE\Http\Exception\NotFoundHttpException;
use SNOWGIRL_CORE\Http\Exception\ServiceUnavailableHttpException;
use SNOWGIRL_CORE\RBAC;
use SNOWGIRL_CORE\AbstractRequest;
use Throwable;
use SNOWGIRL_CORE\View\Widget\Ad\Adaptive as AdaptiveAd;

/**
 * Class HttpApp
 *
 * @property HttpRequest request
 * @property HttpResponse response
 * @property Router router
 *
 * @package SNOWGIRL_CORE\App
 */
class HttpApp extends AbstractApp
{
    protected function get(string $k)
    {
        switch ($k) {
            case 'request':
                return $this->container->getObject('Http\HttpRequest', $this);
            case 'response':
                return $this->container->getObject('Http\HttpResponse');
            case 'router':
                return $this->getRouterObject();
            default:
                return parent::get($k);
        }
    }

    protected function addRoutes(Router $router): HttpApp
    {
        false && $router;

        return $this;
    }

    protected function addFakeRoutes(Router $router): HttpApp
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

    protected function logError(array &$error, string $handler)
    {
        try {
            $uri = $this->request->getServer('REQUEST_URI');
        } catch (Throwable $ex) {
            $uri = null;
        }

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
            '[' . $handler . '_handler] on ' . $uri,
            '[' . $error['type'] . '] ' . $error['message'] . ' in ' . $error['file'] . '(' . $error['line'] . ')',
            $trace
        ]));

        return $this;
    }

    public function logRequest()
    {
        $this->container->logger->debug(implode(' ', [
            '[' . $this->request->getMethod() . ']',
            '[client=' . ($this->request->getClient()->isLoggedIn() ? $this->request->getClient()->getUser()->getId() : '') . ']',
            $this->request->getServer('REQUEST_URI')
        ]));
    }

    /**
     * @throws Exception
     */
    public function run()
    {
        parent::run();

        $adminIp = $this->request->isAdminIp();

//        if ($adminIp = $this->request->isAdminIp()) {
//            if ($prof = $this->config->app->profiling(false)) {
//                $this->services->profiler->enable();
//            }
//        }

//        $this->container->logger
//            ->addParamToLog('IP', $this->request->getClientIp())
//            ->setName('web');

        $this->setErrorHandler()
            ->setExceptionHandler(function (array $error) {
                while (ob_get_level()) {
                    ob_end_clean();
                }

                $this->getResponseWithException($error['ex'])
                    ->send(true);
            })
            ->setShutdownHandler(function (array $error) {
                while (ob_get_level()) {
                    ob_end_clean();
                }

                $this->getResponseWithException($error['ex'])
                    ->send(true);
            });

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
            if ($seconds = $this->config('app.maintenance', false)) {
                if (!$adminIp) {
                    throw (new ServiceUnavailableHttpException)->setRetryAfter(max($seconds, 3600));
                }
            }

            $isOk = $this->router->routeCycle($this->request, function (AbstractRequest $request) {
                $this->request = $request;
                return $this->runAction();
            });

            if (!$isOk) {
                throw new NotFoundHttpException;
            }
        } catch (NotFoundHttpException $e) {
            $this->container->logger->info($e);
            $this->getResponseWithException($e);
        } catch (Throwable $e) {
            $this->container->logger->error($e);
            $this->getResponseWithException($e);
        }

        $this->response->send();
//        $this->logPerformance();

//        if (isset($prof) && $prof) {
//            $this->services->profiler->save();
//        }
    }

    /**
     * @param Throwable $ex
     *
     * @return HttpResponse
     * @throws Exception
     */
    public function getResponseWithException(Throwable $ex): HttpResponse
    {
        if ($ex instanceof HttpException) {
            $code = $ex->getHttpCode();
            $ex->processResponse($this->response);
        } else {
            $code = 500;
        }

        $text = $this->trans->makeText('error.code-' . $code);
        $uri = str_replace(['http://', 'https://'], '', $this->request->getLink(true));

        if ($this->request->isJSON()) {
            return $this->response->setJSON($code, str_replace('{uri}', $uri, $text));
        }

        if ($this->request->isPathFile()) {
            return $this->response->setHTML($code);
        }

        $title = $code;

        if ($reason = $this->response->getReasonByCode($code)) {
            $title .= ' ' . $reason;
        }

        $text = str_replace('{uri}', '<span class="uri">' . $uri . '</span>', $text);

        $view = $this->views->getLayout(false, ['error' => $ex]);

        $banner = null;

        if (404 == $code) {
            if (!$this->request->getDevice()->isMobile()) {
                $banner = $this->ads->findBanner(AdaptiveAd::class, 'common', [], $view);
            }
        }

        $view->setContentByTemplate('error.phtml', [
            'code' => $code,
            'h1' => $title,
            'text' => $text,
            'referer' => $this->request->getReferer(),
            'banner' => $banner,
            'ex' => $ex,
            'showSuggestions' => !in_array($code, [500, 503]),
            'showTrace' => $this->rbac->hasPerm(RBAC::PERM_SHOW_TRACE)
        ]);

        return $this->response->setHTML($code, $view);
    }
}