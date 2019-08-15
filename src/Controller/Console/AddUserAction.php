<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\App\Console as App;
use SNOWGIRL_CORE\Exception\HTTP\BadRequest;

class AddUserAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        if (!$login = $app->request->get('param_1')) {
            throw (new BadRequest)->setInvalidParam('login');
        }

        if (!$password = $app->request->get('param_2')) {
            throw (new BadRequest)->setInvalidParam('password');
        }

        if (!$roleId = $app->request->get('param_3')) {
            throw (new BadRequest)->setInvalidParam('role_id');
        }

        $aff = $app->services->rdbms->insertOne($app->managers->users->getEntity()->getTable(), [
            'login' => $login,
            'password' => md5($password),
            'role_id' => $roleId
        ]);

        $app->response->setBody($aff ? 'DONE' : 'FAILED');
    }
}