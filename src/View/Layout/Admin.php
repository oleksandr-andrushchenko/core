<?php

namespace SNOWGIRL_CORE\View\Layout;

use SNOWGIRL_CORE\AbstractApp;
use SNOWGIRL_CORE\RBAC;
use SNOWGIRL_CORE\View\Layout;

class Admin extends Layout
{
    public function __construct(AbstractApp $app, array $params = [])
    {
        parent::__construct($app, $params);

        $this->addBreadcrumb('Админка', $this->app->router->makeLink('admin'));
        $this->app->seo->setNoIndexNoFollow($this);
    }

    protected function addMenuNodes(): Layout
    {
        $tmp = [];
        $tmp[] = $this->app->rbac->hasPerm(RBAC::PERM_DATABASE_PAGE) ? 'database' : false;
        $tmp[] = $this->app->rbac->hasPerm(RBAC::PERM_PAGES_PAGE) ? 'pages' : false;
        $tmp[] = $this->app->rbac->hasPerm(RBAC::PERM_CONTROL_PAGE) ? 'control' : false;
//        $tmp[] = $this->app->rbac->hasPerm(RBAC::PERM_PROFILER_PAGE) ? 'profiler' : false;

        $tmp = array_filter($tmp, function ($uri) {
            return false !== $uri;
        });

        foreach ($tmp as $action) {
            $this->addMenu($this->makeText('layout.admin.' . $action), $this->makeLink('admin', $action));
        }

        return $this;
    }

    protected function addCssNodes(): Layout
    {
        return parent::addCssNodes()
            ->addHeadCss('@core/admin/core.css');
    }

    protected function addJsNodes(): Layout
    {
        return parent::addJsNodes()
            ->addJs('@core/admin/core.js');
    }

    protected function makeHeader(): string
    {
        return $this->stringifyContent('@core/admin/layout/header.phtml');
    }

    protected function makeBreadcrumbs(): string
    {
        return $this->stringify('@core/layout/breadcrumbs.phtml');
    }

    protected function makeContent(): string
    {
        return $this->stringifyContent('@core/layout/content.phtml');
    }

    protected function makeFooter(): string
    {
        return '';
    }
}