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

class PhpAction
{
    /**
     * @param App $app
     *
     * @throws Forbidden
     */
    public function __invoke(App $app)
    {
        (new PrepareServices)($app);

        if (!$app->request->getClient()->getUser()->isRole(User::ROLE_ADMIN)) {
            throw new Forbidden;
        }

        phpinfo();
    }
}