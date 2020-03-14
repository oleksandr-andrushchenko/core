<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\Console\ConsoleApp as App;
use SNOWGIRL_CORE\Http\Exception\NotFoundHttpException;

class DefaultAction
{
    use PrepareServicesTrait;

    /**
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->response->setBody('NotFound');
    }
}