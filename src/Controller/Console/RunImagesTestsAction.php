<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\Console\ConsoleApp as App;

class RunImagesTestsAction
{
    use PrepareServicesTrait;

    /**
     * @param App $app
     * @throws \SNOWGIRL_CORE\Http\Exception\NotFoundHttpException
     */
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->response->addToBody(implode("\r\n", [
            '',
            __CLASS__,
            $app->images->tests() ? 'DONE' : 'FAILED',
        ]));
    }
}