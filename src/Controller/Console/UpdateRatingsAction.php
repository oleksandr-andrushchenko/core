<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\App\Console as App;

class UpdateRatingsAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->response->setBody($app->analytics->updateRatings() ? 'DONE' : 'FAILED');
    }
}