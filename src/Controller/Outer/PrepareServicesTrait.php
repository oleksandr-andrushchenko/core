<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 9:50 PM
 */

namespace SNOWGIRL_CORE\Controller\Outer;

use SNOWGIRL_CORE\App;

trait PrepareServicesTrait
{
    public function prepareServices(App $app)
    {
        $app->services->logger->setName('web-outer');

        if ($app->request->isAdminIp()) {
            //@todo create event-manager & implemented... (lazy init, debug on create only)
            $app->services->rdbms->debug();
            $app->services->ftdbms->debug();
        }

        if ($app->request->isCrawlerOrBot()) {
            $app->services->mcms->disableSetOperation();
        }

        if ($app->request->isAjax()) {
            $app->seo->setNoIndexNoFollow();
        }
    }
}