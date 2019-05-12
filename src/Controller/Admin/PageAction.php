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
use SNOWGIRL_CORE\Exception\HTTP\NotFound;

class PageAction
{
    /**
     * @param App $app
     *
     * @throws Forbidden
     */
    public function __invoke(App $app)
    {
        (new PrepareServices)($app);

        if (!$app->request->getClient()->getUser()->isRole(User::ROLE_ADMIN, User::ROLE_MANAGER)) {
            throw new Forbidden;
        }

        if (!$id = $app->request->get('id')) {
            throw (new BadRequest)->setInvalidParam('id');
        }

        if (!$page = $app->managers->pagesCustom->find($id)) {
            throw (new NotFound)->setNonExisting('page');
        }

        $view = $app->views->getLayout(true);

        $view->setContentByTemplate('@core/admin/page.phtml', [
            'page' => $page
        ]);

        $app->response->setHTML(200, $view);
    }
}