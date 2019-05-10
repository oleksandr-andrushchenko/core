<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 7/21/17
 * Time: 1:00 PM
 */
namespace SNOWGIRL_CORE\View\Layout;

use SNOWGIRL_CORE\Entity\User;
use SNOWGIRL_CORE\Script\Css;
use SNOWGIRL_CORE\Script\Js;
use SNOWGIRL_CORE\View\Layout;

/**
 * Class Admin
 * @package SNOWGIRL_CORE\View\Layout
 */
class Admin extends Layout
{
    protected function initialize()
    {
        parent::initialize();

        $this->addBreadcrumb('Админка', $this->app->router->makeLink('admin'));
        $this->app->seo->setNoIndexNoFollow($this);
    }

    protected function addMenuNodes()
    {
        $user = $this->client->getUser();

        $tmp = [];
        $tmp[] = $user && $user->isRole(User::ROLE_ADMIN) ? 'database' : false;
        $tmp[] = $user && $user->isRole(User::ROLE_ADMIN, User::ROLE_MANAGER) ? 'pages' : false;
        $tmp[] = $user && $user->isRole(User::ROLE_ADMIN, User::ROLE_MANAGER) ? 'control' : false;
//        $tmp[] = $user && $user->isRole(User::ROLE_ADMIN) ? 'profiler' : false;

        $tmp = array_filter($tmp, function ($uri) {
            return false !== $uri;
        });

        foreach ($tmp as $action) {
            $this->addMenu($this->makeText('layout.admin.' . $action), $this->makeLink('admin', $action));
        }

        return $this;
    }

    protected function addCssNodes()
    {
        return parent::addCssNodes()
            ->addHeadCss(new Css('@core/admin/core.css'));
    }

    protected function addJsNodes()
    {
        return parent::addJsNodes()
            ->addJs(new Js('@core/admin/core.js'));
    }

    protected function makeHeader()
    {
        return $this->stringifyContent('@core/admin/layout/header.phtml');
    }

    protected function makeBreadcrumbs()
    {
        return $this->stringify('@core/layout/breadcrumbs.phtml');
    }

    protected function makeContent()
    {
        return $this->stringifyContent('@core/layout/content.phtml');
    }

    protected function makeFooter()
    {
        return null;
    }
}