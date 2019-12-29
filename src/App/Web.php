<?php

namespace SNOWGIRL_CORE\App;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\Exception\HTTP;
use SNOWGIRL_CORE\Exception\HTTP\NotFound;
use SNOWGIRL_CORE\Exception\HTTP\ServiceUnavailable;
use SNOWGIRL_CORE\RBAC;
use SNOWGIRL_CORE\Request;
use SNOWGIRL_CORE\Response;
use SNOWGIRL_CORE\Service\Logger;
use Throwable;
use SNOWGIRL_CORE\View\Widget\Ad\Adaptive as AdaptiveAd;

class Web extends App
{
    public function run()
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

            $isOk = $this->router->routeCycle($this->request, function (Request $request) {
                $this->request = $request;
                return $this->runAction();
            });

            if (!$isOk) {
                throw new NotFound;
            }
        } catch (NotFound $ex) {
            $this->services->logger->makeException($ex, Logger::TYPE_INFO);
            $this->getResponseWithException($ex);
        } catch (Throwable $ex) {
            $this->services->logger->makeException($ex, Logger::TYPE_ERROR);
            $this->getResponseWithException($ex);
        }

        $this->response->send();
//        $this->logPerformance();

        if (isset($prof) && $prof) {
            $this->services->profiler->save();
        }
    }

    /**
     * @param Throwable $ex
     *
     * @return Response
     */
    public function getResponseWithException(Throwable $ex)
    {
        if ($ex instanceof HTTP) {
            $code = $ex->getHttpCode();
            $ex->processResponse($this->response);
        } else {
            $code = 500;
        }

        $text = $this->trans->makeText('error.code-' . $code);
        $uri = str_replace(['http://', 'https://'], '', $this->request->getLink(true));

        if ($this->request->isJSON()) {
            return $this->response->setJSON($code, str_replace('{uri}', $uri, $text));
        } elseif ($this->request->isPathFile()) {
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