<?php

namespace SNOWGIRL_CORE\Controller\Outer;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\View\Layout\Outer as OuterLayout;

trait ProcessTypicalPageTrait
{
    /**
     * @param App   $app
     * @param       $key
     * @param array $params
     *
     * @return OuterLayout
     * @throws void
     */
    public function processTypicalPage(App $app, $key, array $params = [])
    {
        $app->analytics->logPageHit($key);

//        if ($app->services->mcms->isOn()) {
        $app->services->mcms->prefetch([
            $app->managers->pages->getItemCacheKey($key),
            $app->managers->pages->getMenuCacheKey()
        ]);
//        }

        /** @var OuterLayout $view */
        $view = $app->views->getLayout();
        $app->seo->managePage($key, $view, $params);

        return $view;
    }
}