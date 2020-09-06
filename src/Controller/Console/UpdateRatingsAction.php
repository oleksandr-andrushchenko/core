<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\Console\ConsoleApp as App;
use SNOWGIRL_CORE\Http\Exception\NotFoundHttpException;

/**
 * Class UpdateRatingsAction
 * @package SNOWGIRL_CORE\Controller\Console
 */
class UpdateRatingsAction
{
    use PrepareServicesTrait;

    /**
     * @param App $app
     * @throws NotFoundHttpException
     */
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->response->addToBody(implode("\r\n", [
            '',
            __CLASS__,
            $app->analytics->updateRatings() ? 'DONE' : 'FAILED',
        ]));
    }
}