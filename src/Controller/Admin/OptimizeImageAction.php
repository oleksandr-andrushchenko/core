<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\Http\HttpApp as App;
use SNOWGIRL_CORE\Http\Exception\BadRequestHttpException;
use SNOWGIRL_CORE\RBAC;

class OptimizeImageAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->rbac->checkPerm(RBAC::PERM_ALL);

        if (!$file = $app->request->get('file')) {
            throw (new BadRequestHttpException)->setInvalidParam('file');
        }

        $app->response->setJSON(200, [
            'ok' => $app->images->get('dummy')->optimize($file) ? 1 : 0
        ]);
    }
}