<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:18 PM
 */

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\Entity;

class TranscriptAction
{
    public function __invoke(App $app)
    {
        (new PrepareServices)($app);

        $app->response->setJSON(200, Entity::normalizeUri($app->request->get('src')));
    }
}