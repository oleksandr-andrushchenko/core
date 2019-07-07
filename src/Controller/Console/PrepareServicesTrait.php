<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\App\Console as App;
use SNOWGIRL_CORE\Exception\HTTP\NotFound;

trait PrepareServicesTrait
{
    /**
     * @param App $app
     *
     * @throws void
     */
    public function prepareServices(App $app)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '4096M');

        if (!$app->request->isCli()) {
            throw new NotFound;
        }

//        $app->configMaster = null;
        $app->services->rdbms->debug(false);
        $app->services->mcms->disable();
    }
}
