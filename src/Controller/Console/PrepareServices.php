<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:52 PM
 */

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\Exception\HTTP\NotFound;

class PrepareServices
{
    /**
     * @param App $app
     *
     * @throws NotFound
     */
    public function __invoke(App $app)
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
