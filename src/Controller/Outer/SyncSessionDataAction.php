<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:11 PM
 */

namespace SNOWGIRL_CORE\Controller\Outer;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\Exception\HTTP\MethodNotAllowed;

class SyncSessionDataAction
{
    public function __invoke(App $app)
    {
        (new PrepareServices)($app);

        if (!$app->request->isPost()) {
            throw (new MethodNotAllowed)->setValidMethod('post');
        }

        foreach ($app->request->getPostParam('data', []) as $k => $v) {
            $app->request->getSession()->set($k, $v);
        }

        $app->response->setJSON(200, ['isOk' => true]);
    }
}