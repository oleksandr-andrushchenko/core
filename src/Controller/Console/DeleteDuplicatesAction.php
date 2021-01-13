<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\Console\ConsoleApp as App;
use SNOWGIRL_CORE\Http\Exception\BadRequestHttpException;
use SNOWGIRL_CORE\Mysql\MysqlQuery;

class DeleteDuplicatesAction
{
    use PrepareServicesTrait;

    /**
     * @param App $app
     * @throws \SNOWGIRL_CORE\Http\Exception\NotFoundHttpException
     */
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

        $mysql = $app->container->mysql;

        $query = new MysqlQuery(['params' => []]);
        $query->text = implode(' ', [
            'DELETE ' . $mysql->quote('t1'),
            'FROM ' . $mysql->quote($table) . ' ' . $mysql->quote('t1'),
            'INNER JOIN ' . $mysql->quote($table) . ' ' . $mysql->quote('t2'),
            'WHERE ' . $mysql->quote($pk, 't1') . ' < ' . $mysql->quote($pk, 't2') . ' AND ' . $mysql->quote($column, 't1') . ' = ' . $mysql->quote($column, 't2')
        ]);

        $aff = $mysql->req($query)->affectedRows();

        $app->response->addToBody(implode("\r\n", [
            '',
            __CLASS__,
            'DONE: ' . $aff,
        ]));
    }
}