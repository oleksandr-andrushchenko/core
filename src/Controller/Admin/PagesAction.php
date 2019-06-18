<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:18 PM
 */

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App\Web as App;
use SNOWGIRL_CORE\Entity\User;
use SNOWGIRL_CORE\Exception\HTTP\Forbidden;

class PagesAction
{
    use PrepareServicesTrait;

    /**
     * @param App $app
     *
     * @throws Forbidden
     */
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        if (!$app->request->getClient()->getUser()->isRole(User::ROLE_ADMIN, User::ROLE_MANAGER)) {
            throw new Forbidden;
        }

        $view = $app->views->getLayout(true);

        $view->setContentByTemplate('@core/admin/pages.phtml', [
            'pages' => $app->managers->pages->getObjects()
        ]);

        $app->response->setHTML(200, $view);
    }
}