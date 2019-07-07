<?php

namespace SNOWGIRL_CORE\Controller\Outer;

use SNOWGIRL_CORE\App\Web as App;
use SNOWGIRL_CORE\Exception\HTTP\MethodNotAllowed;
use SNOWGIRL_CORE\View\Layout;

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
                $output = ['isOk' => $isOk, 'body' => $msg];
                $view->addMessage($msg, $isOk ? Layout::MESSAGE_SUCCESS : Layout::MESSAGE_ERROR);
                $app->request->redirectToRoute('default');
            } else {
                $output = ['isOk' => true, 'body' => $form->stringify()];
            }
        } elseif ($app->request->isPost()) {
            $isOk = $form->process($app->request, $msg);
            $output = ['isOk' => $isOk, 'body' => $msg];
        } else {
            throw (new MethodNotAllowed)->setValidMethod(['get', 'post']);
        }

        if ($app->request->isJSON()) {
            $app->response->setJSON(200, $output);
        } elseif ($app->request->isAjax()) {
            $app->response->setHTML(200, $output['body']);
        } else {
            $app->response->setHTML(200, $view->setContent($output['body']));
        }
    }
}