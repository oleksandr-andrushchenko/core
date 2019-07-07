<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App\Web as App;

class Md5Action
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->response->setJSON(200, md5($app->request->get('src')));
    }
}