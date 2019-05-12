<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:55 PM
 */

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\App;

class UpdatePagesAction
{
    public function __invoke(App $app)
    {
        (new PrepareServices)($app);
        
        $app->response->setBody($app->seo->getPages()->update() ? 'DONE' : 'FAILED');
    }
}