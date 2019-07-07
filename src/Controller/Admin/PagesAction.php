<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App\Web as App;
use SNOWGIRL_CORE\RBAC;

class PagesAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->rbac->checkPerm(RBAC::PERM_PAGES_PAGE);

        $view = $app->views->getLayout(true);

        $view->setContentByTemplate('@core/admin/pages.phtml', [
            'pages' => $app->managers->pages->getObjects()
        ]);

        $app->response->setHTML(200, $view);
    }
}