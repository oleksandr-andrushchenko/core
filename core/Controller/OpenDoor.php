<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 7/30/17
 * Time: 9:36 PM
 */

namespace SNOWGIRL_CORE\Controller;

use SNOWGIRL_CORE\Controller;
use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\Exception\HTTP\BadRequest;
use SNOWGIRL_CORE\Exception\HTTP\MethodNotAllowed;
use SNOWGIRL_CORE\Exception\HTTP\NotFound;
use SNOWGIRL_CORE\Helper\Data as DataHelper;
use SNOWGIRL_CORE\Manager;
use SNOWGIRL_CORE\View\Layout;

/**
 * Class OpenDoor
 * @package SNOWGIRL_CORE\Controller
 */
class OpenDoor extends Controller
{
    protected function initialize()
    {
        $this->app->services->logger->setName('open-door');

        if ($this->app->request->isAdminIp()) {
            //@todo create event-manager & implemented... (lazy init, debug on create only)
            $this->app->services->rdbms->debug();
            $this->app->services->ftdbms->debug();
        }

        parent::initialize();

        if ($this->app->request->isCrawlerOrBot()) {
            $this->app->services->mcms->disableSetOperation();
        }

        if ($this->app->request->isAjax()) {
            $this->app->seo->setNoIndexNoFollow();
        }
    }

    protected function addVerifications(Layout\OpenDoor $view)
    {
        foreach ($this->app->config->site->verification_meta([]) as $k => $v) {
            $view->addMeta($k, $v);
        }

        return $this;
    }

    /**
     * @todo !!!!
     * @return bool
     */
    protected function checkRedirect()
    {
        if ($this->app->config->app->check_redirects(false)) {
            //@todo...
            return false;
        }

        return false;
    }

    protected function checkCustomPage()
    {
        if ($this->app->config->app->check_custom_pages(false)) {
            if ($page = $this->app->managers->pagesCustom->findActiveByUri($this->app->request->getPathInfo())) {
                $view = $this->app->views->getLayout();

                $reqUri = $this->app->managers->pagesCustom->getLink($page);
                $rawReqUri = $this->app->request->getLink();

                if ($reqUri != $rawReqUri) {
                    $view->setCanonical($reqUri);
                }

                $this->app->seo->addMeta(
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

                $this->app->response->setHTML(200, $view);

                return true;
            }

            return false;
        }

        return false;
    }

    /**
     * @return bool
     * @throws NotFound
     */
    protected function checkFile()
    {
        if ($this->app->request->isPathFile()) {
            throw new NotFound();
        }

        return false;
    }

    /**
     * @return bool
     * @throws NotFound
     */
    public function actionDefault()
    {
        //@todo move this func to web-server
        if ($this->checkFile()) {
            return true;
        }

        if ($this->checkRedirect()) {
            return true;
        }

        if ($this->checkCustomPage()) {
            return true;
        }

        return parent::actionDefault();
    }

    /**
     * @throws NotFound
     * @throws \SNOWGIRL_CORE\Exception
     */
    public function actionIndex()
    {
        if ('/' != $this->app->request->getPathInfo()) {
            throw new NotFound;
        }

        $params = [];

        $view = $this->processTypicalPage('index', $params);

        $this->app->response->setHTML(200, $view);
    }

    /**
     * @return \SNOWGIRL_CORE\Response
     * @throws \Exception
     */
    public function actionGetSearchSuggestions()
    {
        if (!$query = trim($query = $this->app->request->get('query'))) {
            throw (new BadRequest)->setInvalidParam('query');
        }

        $search = $this->app->views->searchForm();
        $types = $search->getSuggestionsTypes();

        if ($type = $this->app->request->get('type')) {
            if (!in_array($type, $types)) {
                throw (new BadRequest)->setInvalidParam('type');
            }
        } else {
            $type = $types[0];
        }

//        $defaultLimit = intdiv(10, count($types));
//        $defaultLimit = 10;
        $defaultLimit = $search->getParam('suggestionsLimit');

        if (!$limit = min(10, (int)$this->app->request->get('limit', $defaultLimit))) {
            $limit = 10;
        }

        $queries = [];
        $queries[] = $query;

        if (DataHelper::isEnText($query)) {
            $queries[] = DataHelper::keyboardSwitchTo($query, 'ru');
        }

        $output = [];
//        $time = time();

        foreach ($queries as $query) {
            /** @var Manager $manager */
            $manager = $this->app->managers->$type;

//            var_dump($type,$manager);die;

            $display = $manager->findColumns(Entity::SEARCH_DISPLAY)[0];

            $manager->clear()->setLimit($limit);

            foreach (['is_active', 'active'] as $k) {
                if ($manager->getEntity()->hasAttr($k)) {
                    $manager->setWhere([$k => Entity::normalizeBool(true)]);
                }
            }

            foreach ($manager->getObjectsByQuery($query) as $entity) {
                $output[] = [
                    'id' => $entity->getId(),
                    'value' => $value = $entity->get($display),
//                    'tokens' => preg_split("/[\s]+/", $value),
                    'view' => (string)$this->app->views->entity($entity, 'suggestion'),
                    'link' => $manager->getLink($entity),
                    'type' => $type,
//                    'time' => $time
                ];
            }

            if ($output) {
                break;
            }
        }

        return $this->app->response->setJSON(200, $output);
    }

    public function actionGetIpInfo()
    {
        if (!$this->app->request->isGet()) {
            throw (new MethodNotAllowed)->setValidMethod('get');
        }

        if (!$ip = $this->app->request->get('ip')) {
            throw (new BadRequest)->setInvalidParam('ip');
        }

        return $this->app->response->setJSON(200, [
            'country' => $this->app->geo->getCountryByIp($ip)
        ]);
    }

    public function actionSubscribe()
    {
        $view = $this->app->views->getLayout();
        $form = $this->app->views->subscribeForm([], $view);

        if ($this->app->request->isGet()) {
            if ($this->app->request->get('code')) {
                $isOk = $form->confirm($this->app->request, $msg);
                $output = ['isOk' => $isOk, 'body' => $msg];
                $view->addMessage($msg, $isOk ? Layout::MESSAGE_SUCCESS : Layout::MESSAGE_ERROR);
                $this->app->request->redirectToRoute('default');
            } else {
                $output = ['isOk' => true, 'body' => $form->stringify()];
            }
        } elseif ($this->app->request->isPost()) {
            $isOk = $form->process($this->app->request, $msg);
            $output = ['isOk' => $isOk, 'body' => $msg];
        } else {
            throw (new MethodNotAllowed)->setValidMethod(['get', 'post']);
        }

        if ($this->app->request->isJSON()) {
            return $this->app->response->setJSON(200, $output);
        } elseif ($this->app->request->isAjax()) {
            return $this->app->response->setHTML(200, $output['body']);
        } else {
            return $this->app->response->setHTML(200, $view->setContent($output['body']));
        }
    }

    public function actionContact()
    {
        $view = $this->app->views->getLayout();
        $form = $this->app->views->contactForm([], $view);

        if ($this->app->request->isGet()) {
            $output = ['isOk' => true, 'body' => $form->stringify()];
        } elseif ($this->app->request->isPost()) {
            $isOk = $form->process($this->app->request, $msg);
            $view->addMessage($msg, $isOk ? Layout::MESSAGE_SUCCESS : Layout::MESSAGE_ERROR);
            return $this->app->request->redirect($this->app->request->getReferer());
        } else {
            throw (new MethodNotAllowed)->setValidMethod(['get', 'post']);
        }

        if ($this->app->request->isJSON()) {
            return $this->app->response->setJSON(200, $output);
        } elseif ($this->app->request->isAjax()) {
            return $this->app->response->setHTML(200, $output['body']);
        } else {
            return $this->app->response->setHTML(200, $view->setContent($output['body']));
        }
    }

    public function actionSyncSessionData()
    {
        if (!$this->app->request->isPost()) {
            throw (new MethodNotAllowed)->setValidMethod('post');
        }

        foreach ($this->app->request->getPostParam('data', []) as $k => $v) {
            $this->app->request->getSession()->set($k, $v);
        }

        return $this->app->response->setJSON(200, ['isOk' => true]);
    }

    /**
     * @param $key
     * @param array $params
     * @return Layout|Layout\OpenDoor
     * @throws \SNOWGIRL_CORE\Exception
     */
    protected function processTypicalPage($key, array $params = [])
    {
        $this->app->analytics->logPageHit($key);

//        if ($this->app->services->mcms->isOn()) {
        $this->app->services->mcms->prefetch([
            $this->app->managers->pagesRegular->getItemCacheKey($key),
            $this->app->managers->pagesRegular->getMenuCacheKey()
        ]);
//        }

        $view = $this->app->views->getLayout();
        $this->app->seo->managePage($key, $view, $params);

        return $view;
    }
}