<?php

namespace SNOWGIRL_CORE\Controller\Outer;

use SNOWGIRL_CORE\Http\HttpApp as App;
use SNOWGIRL_CORE\Http\Exception\NotFoundHttpException;

class IndexAction
{
    use PrepareServicesTrait;
    use ProcessTypicalPageTrait;
    use AddVerificationsTrait;

    /**
     * @param App $app
     *
     * @throws NotFoundHttpException
     * @throws \SNOWGIRL_CORE\Exception
     */
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        if ('/' != $app->request->getPathInfo()) {
            throw new NotFoundHttpException;
        }

        $view = $this->processTypicalPage($app, 'index');

        $this->addVerifications($app, $view);

        $app->response->setHTML(200, $view);
    }
}