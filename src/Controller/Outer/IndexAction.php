<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 5:31 PM
 */

namespace SNOWGIRL_CORE\Controller\Outer;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\Exception\HTTP\NotFound;

class IndexAction
{
    /**
     * @param App $app
     *
     * @return \SNOWGIRL_CORE\Response
     * @throws NotFound
     */
    public function __invoke(App $app)
    {
        (new PrepareServices)($app);

        if ('/' != $app->request->getPathInfo()) {
            throw new NotFound;
        }

        $view = (new ProcessTypicalPage)($app, 'index');

        (new AddVerifications)($app, $view);

        $app->response->setHTML(200, $view);
    }
}