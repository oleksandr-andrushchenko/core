<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:18 PM
 */

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App\Web as App;
use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\Exception\HTTP\BadRequest;
use SNOWGIRL_CORE\Service\Logger;
use SNOWGIRL_CORE\View\Layout;

class LoginAction
{
    use PrepareServicesTrait;

    /**
     * @param App $app
     *
     * @throws Exception
     * @throws Exception\HTTP\Forbidden
     */
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        if ($app->request->getClient()->isLoggedIn()) {
            $app->request->redirect($app->request->get('redirect_uri') ?: $app->router->makeLink('admin'));
        }

        $view = $app->views->getLayout(true);


        $content = $view->setContentByTemplate('@core/admin/login.phtml', [
            'redirect_uri' => $app->request->get('redirect_uri')
        ]);

        if ($app->request->isPost()) {
            try {
                if (!$login = $app->request->get('login')) {
                    throw (new BadRequest)->setInvalidParam('login');
                }

                if (!$password = $app->request->get('password')) {
                    throw (new BadRequest)->setInvalidParam('password');
                }

                if (!($user = $app->managers->users->setWhere(['login' => $login])->getObject())) {
                    throw new Exception('Такого пользователя нет в системе');
                }

                if ($user->getPassword() != md5($password)) {
                    throw new Exception('Не верный пароль');
                }

                $app->request->getClient()->logIn($user);
                $app->request->redirect($app->request->get('redirect_uri') ?: $app->router->makeLink('admin'));
            } catch (Exception $ex) {
                $app->services->logger->makeException($ex, Logger::TYPE_WARN);
                $view->addMessage($ex->getMessage(), Layout::MESSAGE_ERROR);

                $content->addParams([
                    'login' => $app->request->get('login'),
                    'password' => $app->request->get('password')
                ]);
            }
        }

        $app->response->setHTML(200, $view);
        if ($app->request->getClient()->isLoggedIn()) {
            $app->request->redirect($app->request->get('redirect_uri') ?: $app->router->makeLink('admin'));
        }

        $view = $app->views->getLayout(true);


        $content = $view->setContentByTemplate('@core/admin/login.phtml', [
            'redirect_uri' => $app->request->get('redirect_uri')
        ]);

        if ($app->request->isPost()) {
            try {
                if (!$login = $app->request->get('login')) {
                    throw (new BadRequest)->setInvalidParam('login');
                }

                if (!$password = $app->request->get('password')) {
                    throw (new BadRequest)->setInvalidParam('password');
                }

                if (!($user = $app->managers->users->setWhere(['login' => $login])->getObject())) {
                    throw new Exception('Такого пользователя нет в системе');
                }

                if ($user->getPassword() != md5($password)) {
                    throw new Exception('Не верный пароль');
                }

                $app->request->getClient()->logIn($user);
                $app->request->redirect($app->request->get('redirect_uri') ?: $app->router->makeLink('admin'));
            } catch (Exception $ex) {
                $app->services->logger->makeException($ex, Logger::TYPE_WARN);
                $view->addMessage($ex->getMessage(), Layout::MESSAGE_ERROR);

                $content->addParams([
                    'login' => $app->request->get('login'),
                    'password' => $app->request->get('password')
                ]);
            }
        }

        $app->response->setHTML(200, $view);
    }
}