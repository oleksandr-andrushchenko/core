<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\Console\ConsoleApp as App;
use SNOWGIRL_CORE\Http\Exception\BadRequestHttpException;
use SNOWGIRL_CORE\Query;

class ExecuteStorageQueryAction
{
    use PrepareServicesTrait;

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

        $query = new Query($params);

        $aff = $app->container->db->req($query)->affectedRows();

        $app->response->setBody("DONE: {$aff}");
    }
}