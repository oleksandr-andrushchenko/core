<?php

namespace SNOWGIRL_CORE\Controller\Outer;

use SNOWGIRL_CORE\AbstractApp;
use SNOWGIRL_CORE\View\Layout\Outer as OuterLayout;

trait ProcessTypicalPageTrait
{
    /**
     * @param AbstractApp   $app
     * @param       $key
     * @param array $params
     *
     * @return OuterLayout
     * @throws void
     */
    public function processTypicalPage(AbstractApp $app, $key, array $params = [])
    {
        $app->analytics->logPageHit($key);

        $app->container->cache->getMulti([
            $app->managers->pages->getItemCacheKey($key),
            $app->managers->pages->getMenuCacheKey()
        ]);

        /** @var OuterLayout $view */
        $view = $app->views->getLayout();
        $app->seo->managePage($key, $view, $params);

        return $view;
    }
}