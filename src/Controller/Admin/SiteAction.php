<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\Http\HttpApp as App;

class SiteAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->request->redirectToRoute('index');
    }
}