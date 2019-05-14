<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:55 PM
 */

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\App\Console as App;

class DefaultAction
{
    use PrepareServicesTrait;

    /**
     * @todo...
     * php cmd database:migrate-data-from-table-to-table --table-from=table1 --table-to=table2
     * should calls $app->utils->database->doMigrateDataFromTableToTable(table1, table2)
     *
     * @param App $app
     *
     * @throws \SNOWGIRL_CORE\Exception\HTTP\NotFound
     */
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->response->setBody('NotFound');
    }
}