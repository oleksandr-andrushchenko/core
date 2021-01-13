<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\Console\ConsoleApp as App;
use SNOWGIRL_CORE\Http\Exception\BadRequestHttpException;
use SNOWGIRL_CORE\Mysql\MysqlQuery;

class ExecuteStorageQueryAction
{
    use PrepareServicesTrait;

    /**
     * @param App $app
     * @throws \SNOWGIRL_CORE\Http\Exception\NotFoundHttpException
     */
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        if (!$params = $app->request->get('param_1')) {
            throw (new BadRequestHttpException)->setInvalidParam('params_as_json');
        }

        $params = json_decode($params, true);

        if (!is_array($params)) {
            throw (new BadRequestHttpException)->setInvalidParam('params');
        }

        $query = new MysqlQuery($params);

        $aff = $app->container->mysql->req($query)->affectedRows();

        $app->response->addToBody(implode("\r\n", [
            '',
            __CLASS__,
            "DONE: {$aff}",
        ]));
    }
}