<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App\Web as App;

trait DatabaseTrait
{
    protected function getTables(App $app)
    {
        return $app->services->rdbms->getTables();
    }

    protected function getTable(App $app)
    {
        return $app->request->get('table', current($this->getTables($app)));
    }

    protected function getForbiddenColumns(App $app)
    {
        return [$app->managers->getByTable($this->getTable($app))->getEntity()->getPk(), 'created_at', 'updated_at'];
    }
}