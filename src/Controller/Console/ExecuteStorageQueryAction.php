<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\App\Console as App;
use SNOWGIRL_CORE\Exception\HTTP\BadRequest;
use SNOWGIRL_CORE\Service\Storage\Query;

class ExecuteStorageQueryAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        if (!$params = $app->request->get('param_1')) {
            throw (new BadRequest)->setInvalidParam('params_as_json');
        }

        $params = json_decode($params, true);

        if (!is_array($params)) {
            throw (new BadRequest)->setInvalidParam('params');
        }

        $query = new Query($params);

        $aff = $app->storage->mysql->req($query)->affectedRows();

        $app->response->setBody("DONE: {$aff}");
    }
}