<?php

namespace SNOWGIRL_CORE\Controller\Outer;

use SNOWGIRL_CORE\Http\HttpApp as App;
use SNOWGIRL_CORE\Http\Exception\BadRequestHttpException;
use SNOWGIRL_CORE\Http\Exception\MethodNotAllowedHttpException;

class GetIpInfoAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        if (!$app->request->isGet()) {
            throw (new MethodNotAllowedHttpException)->setValidMethod('get');
        }

        if (!$ip = $app->request->get('ip')) {
            throw (new BadRequestHttpException)->setInvalidParam('ip');
        }

        $app->response->setJSON(200, [
            'country' => $app->geo->getCountryByIp($ip)
        ]);
    }
}