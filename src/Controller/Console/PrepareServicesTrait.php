<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\Console\ConsoleApp as App;
use SNOWGIRL_CORE\Http\Exception\NotFoundHttpException;

trait PrepareServicesTrait
{
    /**
     * @param App $app
     *
     * @throws NotFoundHttpException
     */
    public function prepareServices(App $app)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '4096M');

        if ('cli' != PHP_SAPI) {
            throw new NotFoundHttpException;
        }
    }
}
