<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\Http\HttpApp as App;
use SNOWGIRL_CORE\Http\Exception\ForbiddenHttpException;
use SNOWGIRL_CORE\Http\Exception\MethodNotAllowedHttpException;

class ProfilerAction
{
    use PrepareServicesTrait;

    /**
     * @param App $app
     *
     * @throws ForbiddenHttpException
     */
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->request->redirect($app->profiler->getOption('host'));

        if (!$app->request->isGet()) {
            throw (new MethodNotAllowedHttpException)->setValidMethod('get');
        }

        $view = $app->views->getLayout(true);

        $view->setContentByTemplate('@core/admin/profiler.phtml', [
            'host' => $app->profiler->getOption('host')
        ]);

        $app->response->setHTML(200, $view);
    }
}