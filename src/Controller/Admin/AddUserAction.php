<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App\Web as App;
use SNOWGIRL_CORE\Entity\User;
use SNOWGIRL_CORE\RBAC;

class AddUserAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->rbac->checkPerm(RBAC::PERM_ADD_USER);

        $app->services->rdbms->insertOne(User::getTable(), [
            'login' => $app->request->get('login'),
            'password' => md5($app->request->get('password')),
            'role_id' => $app->request->get('role_id')
        ]);

        $app->request->redirectToRoute('admin', [
            'action' => 'login',
            'login' => $app->request->get('login'),
            'role_id' => $app->request->get('role_id')
        ]);;
    }
}