<?php

namespace SNOWGIRL_CORE\Controller\Outer;

use SNOWGIRL_CORE\App\Web as App;
use SNOWGIRL_CORE\Exception\HTTP\NotFound;

class IndexAction
{
    use PrepareServicesTrait;
    use ProcessTypicalPageTrait;
    use AddVerificationsTrait;

    /**
     * @param App $app
     *
     * @throws NotFound
     * @throws \SNOWGIRL_CORE\Exception
     */
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        if ('/' != $app->request->getPathInfo()) {
            throw new NotFound;
        }

        $view = $this->processTypicalPage($app, 'index');

        $this->addVerifications($app, $view);

        $app->response->setHTML(200, $view);
    }
}