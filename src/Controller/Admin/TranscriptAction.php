<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App\Web as App;
use SNOWGIRL_CORE\Entity;

class TranscriptAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->response->setJSON(200, Entity::normalizeUri($app->request->get('src')));
    }
}