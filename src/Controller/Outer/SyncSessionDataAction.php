<?php

namespace SNOWGIRL_CORE\Controller\Outer;

use SNOWGIRL_CORE\App\Web as App;
use SNOWGIRL_CORE\Exception\HTTP\MethodNotAllowed;

class SyncSessionDataAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        if (!$app->request->isPost()) {
            throw (new MethodNotAllowed)->setValidMethod('post');
        }

        foreach ($app->request->getPostParam('data', []) as $k => $v) {
            $app->request->getSession()->set($k, $v);
        }

        $app->response->setJSON(200, ['isOk' => true]);
    }
}