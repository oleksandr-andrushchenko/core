<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 9:59 PM
 */

namespace SNOWGIRL_CORE\Controller\Outer;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\Exception\HTTP\NotFound;

class DefaultAction
{
    /**
     * @param App $app
     *
     * @return bool
     * @throws NotFound
     */
    public function __invoke(App $app)
    {
        (new PrepareServices)($app);

        if ($this->checkFile($app)) {
            return true;
        }

        if ($this->checkRedirect($app)) {
            return true;
        }

        if ($this->checkCustomPage($app)) {
            return true;
        }

        return false;
    }

    /**
     * @todo...
     * @param App $app
     *
     * @return bool
     */
    protected function checkRedirect(App $app)
    {
        if ($app->config->app->check_redirects(false)) {
            //@todo...
            return false;
        }

        return false;
    }

    /**
     * @param App $app
     *
     * @return bool
     */
    protected function checkCustomPage(App $app)
    {
        if ($app->config->app->check_custom_pages(false)) {
            if ($page = $app->managers->pagesCustom->findActiveByUri($app->request->getPathInfo())) {
                $view = $app->views->getLayout();

                $reqUri = $app->managers->pagesCustom->getLink($page);
                $rawReqUri = $app->request->getLink();

                if ($reqUri != $rawReqUri) {
                    $view->setCanonical($reqUri);
                }

                $app->seo->addMeta(
                    $title = $page->getMetaTitle(),
                    $description = $page->getMetaDescription(),
                    $page->getMetaKeywords(),
                    'article',
                    $reqUri,
                    $title,
                    $description,
                    null,
                    null,
                    $view
                );

                $view->setContentByTemplate('custom.phtml', [
                    'h1' => $page->getH1(),
                    'body' => $page->getBody()
                ]);

                $app->response->setHTML(200, $view);

                return true;
            }

            return false;
        }

        return false;
    }

    /**
     * @param App $app
     *
     * @return bool
     * @throws NotFound
     */
    protected function checkFile(App $app)
    {
        if ($app->request->isPathFile()) {
            throw new NotFound;
        }

        return false;
    }
}