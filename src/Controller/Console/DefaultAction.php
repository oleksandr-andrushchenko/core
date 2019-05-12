<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:55 PM
 */

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\App;

class DefaultAction
{
    /**
     * @todo...
     * php cmd database:migrate-data-from-table-to-table --table-from=table1 --table-to=table2
     * should calls $app->utils->database->doMigrateDataFromTableToTable(table1, table2)
     *
     * @param App $app
     *
     * @return \SNOWGIRL_CORE\Response
     */
    public function __invoke(App $app)
    {
        (new PrepareServices)($app);

        $app->response->setBody('NotFound');
    }
}