<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:55 PM
 */

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\App;

class UpdateSitemapAction
{
    public function __invoke(App $app)
    {
        (new PrepareServices)($app);

        $names = null;

        if ($tmp = $app->request->get('param_1', null)) {
            $names = array_map('trim', explode(',', $tmp));
        }

        $app->response->setBody($app->seo->getSitemap()->update($names) ? 'DONE' : 'FAILED');
    }
}