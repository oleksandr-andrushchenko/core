<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:07 PM
 */

namespace SNOWGIRL_CORE\Controller\Outer;

use SNOWGIRL_CORE\App\Web as App;
use SNOWGIRL_CORE\Exception\HTTP\BadRequest;
use SNOWGIRL_CORE\Exception\HTTP\MethodNotAllowed;

class GetIpInfoAction
{
    use PrepareServicesTrait;

    /**
     * @param App $app
     */
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        if (!$app->request->isGet()) {
            throw (new MethodNotAllowed)->setValidMethod('get');
        }

        if (!$ip = $app->request->get('ip')) {
            throw (new BadRequest)->setInvalidParam('ip');
        }

        $app->response->setJSON(200, [
            'country' => $app->geo->getCountryByIp($ip)
        ]);
    }
}