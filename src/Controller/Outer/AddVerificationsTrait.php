<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:14 PM
 */

namespace SNOWGIRL_CORE\Controller\Outer;

use SNOWGIRL_CORE\App\Web as App;
use SNOWGIRL_CORE\View\Layout\Outer;

trait AddVerificationsTrait
{
    public function addVerifications(App $app, Outer $view)
    {
        foreach ($app->config->site->verification_meta([]) as $k => $v) {
            $view->addMeta($k, $v);
        }
    }
}