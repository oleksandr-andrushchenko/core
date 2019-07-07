<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App\Web as App;

class LogoutAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        if ($app->request->getClient()->isLoggedIn()) {
            $app->request->getClient()->logOut();
        }

        $app->request->redirect($app->request->getServer('HTTP_REFERER') ?: $app->router->makeLink('admin'));
    }
}