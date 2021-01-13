<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\Console\ConsoleApp as App;

class FlushMemcacheAction
{
    use PrepareServicesTrait;

    /**
     * @param App $app
     *
     * @throws \SNOWGIRL_CORE\Http\Exception\NotFoundHttpException
     */
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->container->updateDefinition('memcache', ['enabled' => true]);

        $app->response->addToBody(implode("\r\n", [
            '',
            __CLASS__,
            $app->container->memcache->flush() ? 'DONE' : 'FAILED',
        ]));
    }
}