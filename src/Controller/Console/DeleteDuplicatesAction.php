<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\App\Console as App;
use SNOWGIRL_CORE\Exception\HTTP\BadRequest;
use SNOWGIRL_CORE\Service\Storage\Query;

class DeleteDuplicatesAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        if (!$table = $app->request->get('param_1')) {
            throw (new BadRequest)->setInvalidParam('table');
        }

        if (!$column = $app->request->get('param_2')) {
            throw (new BadRequest)->setInvalidParam('column');
        }

        $manager = $app->managers->getByTable($table);
        $pk = $manager->getEntity()->getPk();

        $db = $app->storage->mysql;

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