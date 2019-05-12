<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:18 PM
 */

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App;

class IndexAction
{
    public function __invoke(App $app)
    {
        (new PrepareServices)($app);

        $app->request->redirectToRoute('admin', $this->getDefaultAction());
    }

    protected function getDefaultAction()
    {
        return 'database';
    }
}