<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App\Web as App;
use SNOWGIRL_CORE\RBAC;

class OptimizeWebJpgAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->rbac->checkPerm(RBAC::PERM_ALL);

        $app->response->setContentType('text/html');

        foreach ($app->images->getAllLocalFiles() as $file) {
            echo $app->images->optimize($file) ? '1' : '0';
            echo '<br/>';
        }
    }
}