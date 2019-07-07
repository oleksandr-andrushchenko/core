<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App\Web as App;
use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\Service\Logger;
use SNOWGIRL_CORE\View\Layout;

trait ExecTrait
{
    /**
     * @param App         $app
     * @param null        $text
     * @param \Closure    $fn
     * @param bool        $isAjax
     * @param Layout|null $view
     *
     * @return bool|mixed
     * @throws void
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
        } catch (\Exception $ex) {
            $app->services->logger->makeException($ex, Logger::TYPE_WARN);
            $output = false;

            if ($isAjax) {
                $app->response->setCode(200)
                    ->setContentType('application/json');

                if (!$app->response->getBody()) {
                    $app->response->setBody(json_encode([
                        'ok' => 0,
                        'text' => $ex->getMessage()
                    ]));
                }
            } else {
                $view = $view ?: $app->views->getLayout(true);
                $view->addMessage($ex->getMessage(), Layout::MESSAGE_ERROR);
            }
        }

        return $output;
    }
}