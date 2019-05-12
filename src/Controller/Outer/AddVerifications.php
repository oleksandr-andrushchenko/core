<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:14 PM
 */

namespace SNOWGIRL_CORE\Controller\Outer;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\View\Layout\Outer;

class AddVerifications
{
    public function __invoke(App $app, Outer $view)
    {
        foreach ($app->config->site->verification_meta([]) as $k => $v) {
            $view->addMeta($k, $v);
        }
    }
}