<?php

namespace SNOWGIRL_CORE\Controller\Outer;

use SNOWGIRL_CORE\AbstractApp;
use SNOWGIRL_CORE\Memcache\MemcacheDisableSetOperationDecorator;
use SNOWGIRL_CORE\Memcache\MemcacheInterface;

trait PrepareServicesTrait
{
    public function prepareServices(AbstractApp $app)
    {
        if ($app->request->isAdminIp()) {
            $app->container->updateDefinition('mysql', ['debug' => true])
                ->updateDefinition('elasticsearch', ['debug' => true]);
        }

        if ($app->request->isCrawlerOrBot()) {
            $app->container->updateDefinition('memcache', [], function (MemcacheInterface $memcache) {
                return new MemcacheDisableSetOperationDecorator($memcache);
            });
        }

        if ($app->request->isAjax()) {
            $app->seo->setNoIndexNoFollow();
        }
    }
}