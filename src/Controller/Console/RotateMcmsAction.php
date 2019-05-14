<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:55 PM
 */

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\App\Console as App;

class RotateMcmsAction
{
    use PrepareServicesTrait;

    /**
     * @param App $app
     *
     * @throws \SNOWGIRL_CORE\Exception\HTTP\NotFound
     */
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->response->setBody(implode("\r\n", [
            __CLASS__,
            $app->services->mcms->rotate() ? 'DONE' : 'FAILED'
        ]));
    }
}