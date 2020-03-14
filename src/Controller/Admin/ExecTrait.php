<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\Http\HttpApp as App;
use SNOWGIRL_CORE\View\Layout;
use Throwable;

trait ExecTrait
{
    /**
     * @param App $app
     * @param null $text
     * @param \Closure $fn
     * @param bool $isAjax
     * @param Layout|null $view
     *
     * @return bool|mixed
     * @throws \SNOWGIRL_CORE\Exception
     */
    protected function _exec(App $app, $text = null, \Closure $fn, $isAjax = false, Layout $view = null)
    {
        try {
            $output = $fn($app);
            $text = null === $output ? ($text ?: 'Операция выполнена успешно') : $output;

            if ($isAjax) {
                $app->response->setCode(200)
                    ->setContentType('application/json');

                if (!$app->response->getBody()) {
                    $app->response->setBody(json_encode([
                        'ok' => 1,
                        'text' => $text
                    ]));
                }
            } else {
                $view = $view ?: $app->views->getLayout(true);
                $view->addMessage($text, Layout::MESSAGE_SUCCESS);
            }
        } catch (Throwable $e) {
            $app->container->logger->warning($e);
            $output = false;

            if ($isAjax) {
                $app->response->setCode(200)
                    ->setContentType('application/json');

                if (!$app->response->getBody()) {
                    $app->response->setBody(json_encode([
                        'ok' => 0,
                        'text' => $e->getMessage()
                    ]));
                }
            } else {
                $view = $view ?: $app->views->getLayout(true);
                $view->addMessage($e->getMessage(), Layout::MESSAGE_ERROR);
            }
        }

        return $output;
    }
}