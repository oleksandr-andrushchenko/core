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

class DownloadImageAction
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

        if (!$uri = $app->request->get('uri')) {
            throw (new BadRequest)->setInvalidParam('uri');
        }

        $app->response->setJSON(200, [
            'ok' => $app->images->get('dummy')->optimize($uri) ? 1 : 0
        ]);
    }
}