<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:18 PM
 */

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\Entity\User;
use SNOWGIRL_CORE\Exception\HTTP\Forbidden;

class AddUserAction
{
    use ExecTrait;

    /**
     * @param App $app
     *
     * @throws Forbidden
     * @throws \SNOWGIRL_CORE\Exception
     */
    public function __invoke(App $app)
    {
        (new PrepareServices)($app);

        if (!$app->request->getClient()->getUser()->isRole(User::ROLE_ADMIN)) {
            throw new Forbidden;
        }

        self::_exec($app, 'Логин добавлен', function () use ($app) {
            $app->services->rdbms->insertOne(User::getTable(), [
                'login' => $app->request->get('login'),
                'password' => md5($app->request->get('password')),
                'role' => $app->request->get('role')
            ]);
        });

        $app->request->redirectToRoute('admin', [
            'action' => 'login',
            'login' => $app->request->get('login'),
            'role' => $app->request->get('role')
        ]);;
    }
}