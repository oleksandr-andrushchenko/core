<?php

namespace SNOWGIRL_CORE\Controller\Outer;

use SNOWGIRL_CORE\Http\HttpApp as App;
use SNOWGIRL_CORE\Http\Exception\MethodNotAllowedHttpException;
use SNOWGIRL_CORE\View\Layout;
use SNOWGIRL_CORE\View\Widget\Form\Subscribe;

class SubscribeAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $view = $app->views->getLayout();
        $form = $app->views->subscribeForm([], $view);

        if ($app->request->isGet()) {
            if ($app->request->get('code')) {
                $isOk = $form->confirm($app->request, $msg);

                $output = [
                    'isOk' => $isOk,
                    'body' => $msg,
                ];

                $view->addMessage($msg, $isOk ? Layout::MESSAGE_SUCCESS : Layout::MESSAGE_ERROR);

                $app->request->redirectToRoute('default');
            } else {
                $output = [
                    'isOk' => true,
                    'body' => $form->stringify(),
                ];
            }
        } elseif ($app->request->isPost()) {
            if ($this->validate($app, $form)) {
                $isOk = $form->process($app->request, $msg);

                $output = [
                    'isOk' => $isOk,
                    'body' => $msg,
                ];
            } else {
                $output = [
                    'isOk' => false,
                    'body' => '',
                ];
            }
        } else {
            throw (new MethodNotAllowedHttpException)->setValidMethod(['get', 'post']);
        }

        if ($app->request->isJSON()) {
            $app->response->setJSON(200, $output);
        } elseif ($app->request->isAjax()) {
            $app->response->setHTML(200, $output['body']);
        } else {
            $app->response->setHTML(200, $view->setContent($output['body']));
        }
    }

    private function validate(App $app, Subscribe $subscribeForm)
    {
        $name = $subscribeForm->getParam('name');

        if ($name && preg_match('/^[a-zA-Z]{10}$/', $name)) {
            $app->container->logger->info('subscribe validation failed', [
                'name' => $name,
                'email' => $subscribeForm->getParam('email'),
            ]);

            return false;
        }

        return true;
    }
}