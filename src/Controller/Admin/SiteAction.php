<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:18 PM
 */

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App;

class SiteAction
{
    public function __invoke(App $app)
    {
        (new PrepareServices)($app);

        $app->request->redirectToRoute('index');
    }
}