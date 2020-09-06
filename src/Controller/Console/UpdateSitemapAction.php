<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\Console\ConsoleApp as App;
use SNOWGIRL_CORE\Http\Exception\NotFoundHttpException;

/**
 * Class UpdateSitemapAction
 * @package SNOWGIRL_CORE\Controller\Console
 */
class UpdateSitemapAction
{
    use PrepareServicesTrait;

    /**
     * @param App $app
     * @throws NotFoundHttpException
     */
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $names = null;

        if ($tmp = $app->request->get('param_1', null)) {
            $names = array_map('trim', explode(',', $tmp));
        }

        $aff = $app->utils->sitemap->doGenerate($names);

        $app->response->addToBody(implode("\r\n", [
            '',
            __CLASS__,
            "DONE: {$aff}",
        ]));
    }
}