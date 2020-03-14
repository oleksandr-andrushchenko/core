<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\Console\ConsoleApp as App;

class FlushCacheAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->response->addToBody(implode("\r\n", [
            __CLASS__,
            $app->container->cache->flush() ? 'DONE' : 'FAILED'
        ]));
    }
}