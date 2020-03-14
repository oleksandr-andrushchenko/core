<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\Http\HttpApp as App;
use SNOWGIRL_CORE\RBAC;

class FlushCacheAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->rbac->checkPerm(RBAC::PERM_ROTATE_MCMS);

        $app->container->cache->flush();

        $app->request->redirectBack();
    }
}