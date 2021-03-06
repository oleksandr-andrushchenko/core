<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\Http\HttpApp as App;
use SNOWGIRL_CORE\Http\Exception\BadRequestHttpException;
use SNOWGIRL_CORE\RBAC;

class DownloadImageAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->rbac->checkPerm(RBAC::PERM_ALL);

        if (!$uri = $app->request->get('uri')) {
            throw (new BadRequestHttpException)->setInvalidParam('uri');
        }

        $app->response->setJSON(200, [
            'ok' => $app->images->get('dummy')->optimize($uri) ? 1 : 0
        ]);
    }
}