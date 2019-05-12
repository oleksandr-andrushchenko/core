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
use SNOWGIRL_CORE\Exception\HTTP\BadRequest;
use SNOWGIRL_CORE\Exception\HTTP\Forbidden;

class OptimizeWebJpgAction
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

        $img = $app->images->get('dummy');

        $app->response->setContentType('text/html');

        foreach (glob($app->dirs['@public'] . '/img/*.jpg') as $image) {
            echo $img->optimize($image) ? '1' : '0';
            echo '<br/>';
        }
    }
}