<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App\Web as App;
use SNOWGIRL_CORE\RBAC;

class ControlAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->rbac->checkPerm(RBAC::PERM_CONTROL_PAGE);

        $view = $app->views->getLayout(true);

        $view->setContentByTemplate('@core/admin/control.phtml', [
            'buttons' => $this->getButtons($app)
        ]);

        $app->response->setHTML(200, $view);
    }

    protected function getButtons(App $app): array
    {
        $tmp = [];

        if ($app->rbac->hasPerm(RBAC::PERM_GENERATE_SITEMAP)) {
            $tmp[] = [
                'text' => 'Sitemap',
                'icon' => 'refresh',
                'class' => 'info',
                'action' => 'generate-sitemap'
            ];
        }

        if ($app->rbac->hasPerm(RBAC::PERM_ROTATE_MCMS)) {
            $tmp[] = [
                'text' => 'Rotate MCMS (cache: memcache, redis etc.)',
                'icon' => 'refresh',
                'class' => 'warning',
                'action' => 'rotate-cache'
            ];
        }

        if ($app->rbac->hasPerm(RBAC::PERM_ROTATE_FTDMS)) {
            $tmp[] = [
                'text' => 'Rotate FTDBMS (search: elastic etc.)',
                'icon' => 'refresh',
                'class' => 'default',
                'action' => 'rotate-ftdbms'
            ];
        }

        return $tmp;
    }
}