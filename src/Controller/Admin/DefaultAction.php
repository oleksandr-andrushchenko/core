<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\Http\HttpApp as App;

class DefaultAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->request->redirectToRoute('admin', 'index');
    }
}