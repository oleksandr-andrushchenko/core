<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:18 PM
 */

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App\Web as App;
use SNOWGIRL_CORE\Entity\User;
use SNOWGIRL_CORE\Exception\HTTP\Forbidden;

class GenerateSitemapAction
{
    use PrepareServicesTrait;
    use ExecTrait;

    /**
     * @param App $app
     *
     * @throws Forbidden
     * @throws \SNOWGIRL_CORE\Exception
     */
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        if (!$app->request->getClient()->getUser()->isRole(User::ROLE_ADMIN, User::ROLE_MANAGER)) {
            throw new Forbidden;
        }

        self::_exec($app, 'Карта сайта успешно сгенерирована', function () use ($app) {
            $app->seo->getSitemap()->update();
        });

        $app->request->redirectToRoute('admin', 'control');
    }
}