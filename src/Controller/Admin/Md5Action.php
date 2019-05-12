<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:18 PM
 */

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App;

class Md5Action
{
    public function __invoke(App $app)
    {
        (new PrepareServices)($app);

        $app->response->setJSON(200, md5($app->request->get('src')));
    }
}