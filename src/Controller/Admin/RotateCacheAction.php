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

class RotateCacheAction
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

        self::_exec($app, 'Кэш успешно обновлен', function () use ($app) {
            $app->services->mcms->rotate();
        });

        $app->request->redirectToRoute('admin', 'control');
    }
}