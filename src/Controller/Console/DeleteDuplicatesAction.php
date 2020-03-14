<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\Console\ConsoleApp as App;
use SNOWGIRL_CORE\Http\Exception\BadRequestHttpException;
use SNOWGIRL_CORE\Query;

class DeleteDuplicatesAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        if (!$table = $app->request->get('param_1')) {
            throw (new BadRequestHttpException)->setInvalidParam('table');
        }

        if (!$column = $app->request->get('param_2')) {
            throw (new BadRequestHttpException)->setInvalidParam('column');
        }

        $manager = $app->managers->getByTable($table);
        $pk = $manager->getEntity()->getPk();

        $db = $app->container->db;

        $query = new Query(['params' => []]);
        $query->text = implode(' ', [
            'DELETE ' . $db->quote('t1'),
            'FROM ' . $db->quote($table) . ' ' . $db->quote('t1'),
            'INNER JOIN ' . $db->quote($table) . ' ' . $db->quote('t2'),
            'WHERE ' . $db->quote($pk, 't1') . ' < ' . $db->quote($pk, 't2') . ' AND ' . $db->quote($column, 't1') . ' = ' . $db->quote($column, 't2')
        ]);

        $aff = $db->req($query)->affectedRows();

        $app->response->setBody('DONE: ' . $aff);
    }
}