<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:18 PM
 */

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\Entity\User;
use SNOWGIRL_CORE\Exception\HTTP\BadRequest;
use SNOWGIRL_CORE\Exception\HTTP\Forbidden;

class OptimizeImageAction
{
    /**
     * @param App $app
     *
     * @return \SNOWGIRL_CORE\Response
     * @throws Forbidden
     */
    public function __invoke(App $app)
    {
        (new PrepareServices)($app);

        if (!$app->request->getClient()->getUser()->isRole(User::ROLE_ADMIN)) {
            throw new Forbidden;
        }

        if (!$file = $app->request->get('file')) {
            throw (new BadRequest)->setInvalidParam('file');
        }

        $app->response->setJSON(200, [
            'ok' => $app->images->get('dummy')->optimize($file) ? 1 : 0
        ]);
    }
}