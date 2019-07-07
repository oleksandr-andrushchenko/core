<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App\Web as App;

class DefaultAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->request->redirectToRoute('admin', 'index');
    }
}