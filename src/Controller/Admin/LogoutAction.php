<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:18 PM
 */

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App\Web as App;

class LogoutAction
{
    use PrepareServicesTrait;

    /**
     * @param App $app
     *
     * @throws \SNOWGIRL_CORE\Exception\HTTP\Forbidden
     */
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        if ($app->request->getClient()->isLoggedIn()) {
            $app->request->getClient()->logOut();
        }

        $app->request->redirect($app->request->getServer('HTTP_REFERER') ?: $app->router->makeLink('admin'));
    }
}