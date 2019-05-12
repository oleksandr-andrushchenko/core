<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:55 PM
 */

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\App;

class DropRatingsAction
{
    public function __invoke(App $app)
    {
        (new PrepareServices)($app);

        $app->response->setBody($app->analytics->dropRatings() ? 'DONE' : 'FAILED');
    }
}