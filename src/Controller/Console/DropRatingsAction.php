<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\App\Console as App;

class DropRatingsAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->response->setBody($app->analytics->dropRatings() ? 'DONE' : 'FAILED');
    }
}