<?php

namespace SNOWGIRL_CORE\Controller\Outer;

use SNOWGIRL_CORE\Http\HttpApp as App;
use SNOWGIRL_CORE\View\Layout\Outer;

trait AddVerificationsTrait
{
    public function addVerifications(App $app, Outer $view)
    {
        foreach ($app->config('site.verification_meta', []) as $k => $v) {
            $view->addMeta($k, $v);
        }
    }
}