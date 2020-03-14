<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\Http\HttpApp as App;
use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\Http\Exception\BadRequestHttpException;
use SNOWGIRL_CORE\View\Layout;

class LoginAction
{
    use PrepareServicesTrait;

    /**
     * @param App $app
     *
     * @throws Exception
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
                    throw (new BadRequestHttpException)->setInvalidParam('login');
                }

                if (!$password = $app->request->get('password')) {
                    throw (new BadRequestHttpException)->setInvalidParam('password');
                }

                if (!($user = $app->managers->users->setWhere(['login' => $login])->getObject())) {
                    throw new Exception('Такого пользователя нет в системе');
                }

                if ($user->getPassword() != md5($password)) {
                    throw new Exception('Не верный пароль');
                }

                $app->request->getClient()->logIn($user);
                $app->request->redirect($app->request->get('redirect_uri') ?: $app->router->makeLink('admin'));
            } catch (Exception $e) {
                $app->container->logger->warning($e);
                $view->addMessage($e->getMessage(), Layout::MESSAGE_ERROR);

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
                    throw (new BadRequestHttpException)->setInvalidParam('login');
                }

                if (!$password = $app->request->get('password')) {
                    throw (new BadRequestHttpException)->setInvalidParam('password');
                }

                if (!($user = $app->managers->users->setWhere(['login' => $login])->getObject())) {
                    throw new Exception('Такого пользователя нет в системе');
                }

                if ($user->getPassword() != md5($password)) {
                    throw new Exception('Не верный пароль');
                }

                $app->request->getClient()->logIn($user);
                $app->request->redirect($app->request->get('redirect_uri') ?: $app->router->makeLink('admin'));
            } catch (Exception $e) {
                $app->container->logger->warning($e);
                $view->addMessage($e->getMessage(), Layout::MESSAGE_ERROR);

                $content->addParams([
                    'login' => $app->request->get('login'),
                    'password' => $app->request->get('password')
                ]);
            }
        }

        $app->response->setHTML(200, $view);
    }
}