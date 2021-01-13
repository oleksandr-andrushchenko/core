<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\Console\ConsoleApp as App;
use SNOWGIRL_CORE\Http\Exception\BadRequestHttpException;

class AddUserAction
{
    use PrepareServicesTrait;

    /**
     * @param App $app
     * @throws \SNOWGIRL_CORE\Http\Exception\NotFoundHttpException
     */
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        if (!$login = $app->request->get('param_1')) {
            throw (new BadRequestHttpException)->setInvalidParam('login');
        }

        if (!$password = $app->request->get('param_2')) {
            throw (new BadRequestHttpException)->setInvalidParam('password');
        }

        if (!$roleId = $app->request->get('param_3')) {
            throw (new BadRequestHttpException)->setInvalidParam('role_id');
        }

        $aff = $app->container->mysql->insertOne($app->managers->users->getEntity()->getTable(), [
            'login' => $login,
            'password' => md5($password),
            'role_id' => $roleId
        ]);

        $app->response->addToBody(implode("\r\n", [
            '',
            __CLASS__,
            $aff ? 'DONE' : 'FAILED',
        ]));
    }
}