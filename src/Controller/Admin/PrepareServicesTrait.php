<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\Http\HttpApp as App;
use SNOWGIRL_CORE\Cache\CacheInterface;
use SNOWGIRL_CORE\Http\Exception\ForbiddenHttpException;
use SNOWGIRL_CORE\Cache\Decorator\DisableGetOperationCacheDecorator;
use SNOWGIRL_CORE\Cache\Decorator\DisableSetOperationCacheDecorator;
use SNOWGIRL_CORE\RBAC;

trait PrepareServicesTrait
{
    /**
     * @param App $app
     *
     * @throws ForbiddenHttpException
     * @throws \SNOWGIRL_CORE\Exception
     */
    public function prepareServices(App $app)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '4096M');

        if ('ru_RU' != $app->trans->getLocale()) {
            $app->trans->setLocales(['default' => 'ru_RU'])->setLocale('ru_RU');
        }

        $app->seo->setNoIndexNoFollow();

        if (!$app->request->isAdminIp()) {
            throw new ForbiddenHttpException;
        }

        $app->container->updateDefinition('cache', [], function (CacheInterface $cache) {
            return new DisableGetOperationCacheDecorator(
                new DisableSetOperationCacheDecorator($cache)
            );
        });

        $app->logRequest();

        if (!$app->request->getClient()->isLoggedIn() && !in_array($app->request->getAction(), ['login', 'add-user', 'site'])) {
            $app->request->redirectToRoute('admin', [
                'action' => 'login',
                'redirect_uri' => $app->request->getUri()
            ]);
        }

        if ($app->request->getClient()->isLoggedIn() && $app->rbac->hasRole(RBAC::ROLE_NONE)) {
            throw new ForbiddenHttpException;
        }

        $app->trans->setLocale('ru_RU');
    }
}