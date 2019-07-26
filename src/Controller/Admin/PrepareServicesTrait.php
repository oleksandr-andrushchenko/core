<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App\Web as App;
use SNOWGIRL_CORE\Exception\HTTP\Forbidden;
use SNOWGIRL_CORE\RBAC;

trait PrepareServicesTrait
{
    /**
     * @param App $app
     *
     * @throws void
     */
    public function prepareServices(App $app)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '4096M');

        if ('ru_RU' != $app->trans->getLocale()) {
            $app->trans->setLocales(['default' => 'ru_RU'])->setLocale('ru_RU');
        }

        $app->services->logger->setName('web-admin')
            ->enable();

        $app->seo->setNoIndexNoFollow();

        if (!$app->request->isAdminIp()) {
            throw new Forbidden;
        }

        $app->services->rdbms->debug(false);

        $app->services->mcms->disableSetOperation()
            ->disableGetOperation();

        $app->logRequest();

        if (!$app->request->getClient()->isLoggedIn() && !in_array($app->request->getAction(), ['login', 'add-user', 'site'])) {
            $app->request->redirectToRoute('admin', [
                'action' => 'login',
                'redirect_uri' => $app->request->getUri()
            ]);
        }

        if ($app->request->getClient()->isLoggedIn() && $app->rbac->hasRole(RBAC::ROLE_NONE)) {
            throw new Forbidden;
        }

        $app->trans->setLocale('ru_RU');
    }
}