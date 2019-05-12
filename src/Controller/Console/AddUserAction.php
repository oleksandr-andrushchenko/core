<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:18 PM
 */

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\Entity\User;
use SNOWGIRL_CORE\Exception\HTTP\BadRequest;

class AddUserAction
{
    public function __invoke(App $app)
    {
        (new PrepareServices)($app);

        if (!$login = $app->request->get('param_1')) {
            throw (new BadRequest)->setInvalidParam('login');
        }

        if (!$password = $app->request->get('param_2')) {
            throw (new BadRequest)->setInvalidParam('password');
        }

        if (!$role = $app->request->get('param_3')) {
            throw (new BadRequest)->setInvalidParam('role');
        }

        $aff = $app->services->rdbms->insertOne(User::getTable(), [
            'login' => $login,
            'password' => md5($password),
            'role' => $role
        ]);

        $app->response->setBody($aff ? 'DONE' : 'FAILED');
    }
}