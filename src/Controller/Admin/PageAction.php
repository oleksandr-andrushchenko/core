<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\Http\HttpApp as App;
use SNOWGIRL_CORE\Http\Exception\BadRequestHttpException;
use SNOWGIRL_CORE\Http\Exception\NotFoundHttpException;
use SNOWGIRL_CORE\RBAC;

class PageAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->rbac->checkPerm(RBAC::PERM_PAGE_PAGE);

        if (!$id = $app->request->get('id')) {
            throw (new BadRequestHttpException)->setInvalidParam('id');
        }

        if (!$page = $app->managers->pages->find($id)) {
            throw (new NotFoundHttpException)->setNonExisting('page');
        }

        $view = $app->views->getLayout(true);

        $view->setContentByTemplate('@core/admin/page.phtml', [
            'page' => $page
        ]);

        $app->response->setHTML(200, $view);
    }
}