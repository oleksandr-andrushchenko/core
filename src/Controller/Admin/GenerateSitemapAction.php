<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\Http\HttpApp as App;
use SNOWGIRL_CORE\RBAC;

class GenerateSitemapAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->rbac->checkPerm(RBAC::PERM_GENERATE_SITEMAP);

        $app->seo->getSitemap()->update();

        $app->request->redirectBack();
    }
}