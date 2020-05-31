<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\Console\ConsoleApp as App;

class UpdatePagesAction
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
            ($aff = $app->seo->getPages()->update()) ? "DONE: {$aff}" : 'FAILED',
        ]));
    }
}