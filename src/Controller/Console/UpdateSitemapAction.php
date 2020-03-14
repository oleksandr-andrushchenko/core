<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\Console\ConsoleApp as App;

class UpdateSitemapAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $names = null;

        if ($tmp = $app->request->get('param_1', null)) {
            $names = array_map('trim', explode(',', $tmp));
        }

        $app->response->setBody($app->seo->getSitemap()->update($names) ? 'DONE' : 'FAILED');
    }
}