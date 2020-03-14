<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\Http\HttpApp as App;

class IndexAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->request->redirectToRoute('admin', $this->getDefaultAction($app));
    }

    protected function getDefaultAction(App $app): string
    {
        return 'database';
    }
}