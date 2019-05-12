<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:18 PM
 */

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\Exception\HTTP\MethodNotAllowed;

class ProfilerAction
{
    /**
     * @todo...
     * @param App $app
     *
     * @return \SNOWGIRL_CORE\Response
     */
    public function __invoke(App $app)
    {
        (new PrepareServices)($app);

        $app->request->redirect($app->profiler->getOption('host'));

        if (!$app->request->isGet()) {
            throw (new MethodNotAllowed)->setValidMethod('get');
        }

        $view = $app->views->getLayout(true);

        $view->setContentByTemplate('@core/admin/profiler.phtml', [
            'host' => $app->profiler->getOption('host')
        ]);

        $app->response->setHTML(200, $view);
    }
}