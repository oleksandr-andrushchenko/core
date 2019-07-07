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

        $img = $app->images->get('dummy');

        $app->response->setContentType('text/html');

        foreach (glob($app->dirs['@public'] . '/img/*.jpg') as $image) {
            echo $img->optimize($image) ? '1' : '0';
            echo '<br/>';
        }
    }
}