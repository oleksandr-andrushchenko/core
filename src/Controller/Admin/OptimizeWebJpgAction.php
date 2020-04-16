<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\Http\HttpApp as App;
use SNOWGIRL_CORE\RBAC;

class OptimizeWebJpgAction
{
    use PrepareServicesTrait;

    /**
     * @param App $app
     * @throws \SNOWGIRL_CORE\Exception
     * @throws \SNOWGIRL_CORE\Http\Exception\ForbiddenHttpException
     */
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->rbac->checkPerm(RBAC::PERM_ALL);

        $app->response->setContentType('text/html');

        $app->images->walkLocal('*', '*', '*', function (array $files) use ($app) {
            foreach ($files as $file) {
                echo $app->images->optimize($file) ? '1' : '0';
                echo '<br/>';
            }
        });
    }
}