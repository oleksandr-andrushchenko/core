<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 5:34 PM
 */

namespace SNOWGIRL_CORE\Controller\Outer;

use SNOWGIRL_CORE\App;

class ProcessTypicalPage
{
    /**
     * @param App   $app
     * @param       $key
     * @param array $params
     *
     * @return \SNOWGIRL_CORE\View\Layout
     * @throws \SNOWGIRL_CORE\Exception
     */
    public function __invoke(App $app, $key, array $params = [])
    {
        $app->analytics->logPageHit($key);

//        if ($app->services->mcms->isOn()) {
        $app->services->mcms->prefetch([
            $app->managers->pagesRegular->getItemCacheKey($key),
            $app->managers->pagesRegular->getMenuCacheKey()
        ]);
//        }

        $view = $app->views->getLayout();
        $app->seo->managePage($key, $view, $params);

        return $view;
    }
}