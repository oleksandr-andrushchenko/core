<?php

namespace SNOWGIRL_CORE\Controller\Outer;

use SNOWGIRL_CORE\AbstractApp;
use SNOWGIRL_CORE\Cache\CacheInterface;
use SNOWGIRL_CORE\Cache\Decorator\DisableSetOperationCacheDecorator;

trait PrepareServicesTrait
{
    public function prepareServices(AbstractApp $app)
    {
        if ($app->request->isAdminIp()) {
            $app->container->updateDefinition('db', ['debug' => true])
                ->updateDefinition('indexer', ['debug' => true]);
        }

        if ($app->request->isCrawlerOrBot()) {
            $app->container->updateDefinition('cache', [], function (CacheInterface $cache) {
                return new DisableSetOperationCacheDecorator($cache);
            });
        }

        if ($app->request->isAjax()) {
            $app->seo->setNoIndexNoFollow();
        }
    }
}