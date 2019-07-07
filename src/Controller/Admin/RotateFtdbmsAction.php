<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App\Web as App;
use SNOWGIRL_CORE\RBAC;
use SNOWGIRL_CORE\Service\Ftdbms\Elastic;
use SNOWGIRL_CORE\Service\Ftdbms\Sphinx;

class RotateFtdbmsAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->rbac->checkPerm(RBAC::PERM_ROTATE_FTDMS);

        switch (get_class($app->services->ftdbms)) {
            case Sphinx::class:
                $app->utils->sphinx->doRotate();
                break;
            case Elastic::class:
                break;
            default:
                break;
        }

        $app->request->redirectBack();
    }
}