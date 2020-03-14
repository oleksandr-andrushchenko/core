<?php

namespace SNOWGIRL_CORE\Controller\Outer;

use SNOWGIRL_CORE\Http\HttpApp as App;
use SNOWGIRL_CORE\Http\Exception\MethodNotAllowedHttpException;

class SyncSessionDataAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        if (!$app->request->isPost()) {
            throw (new MethodNotAllowedHttpException)->setValidMethod('post');
        }

        foreach ($app->request->getPostParam('data', []) as $k => $v) {
            $app->request->getSession()->set($k, $v);
        }

        $app->response->setJSON(200, ['isOk' => true]);
    }
}