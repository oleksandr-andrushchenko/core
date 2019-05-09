<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 3/7/16
 * Time: 6:47 AM
 */

namespace SNOWGIRL_CORE\Controller;

use SNOWGIRL_CORE\Response;
use SNOWGIRL_CORE\Service\Logger;
use SNOWGIRL_CORE\Controller;
use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\Helper\Arrays;
use SNOWGIRL_CORE\Image;
use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\Exception\HTTP\BadRequest;
use SNOWGIRL_CORE\Exception\HTTP\Forbidden;
use SNOWGIRL_CORE\Exception\HTTP\NotFound;
use SNOWGIRL_CORE\Entity\User;
use SNOWGIRL_CORE\Manager;
use SNOWGIRL_CORE\View\Layout;
use SNOWGIRL_CORE\Service\Storage\Query\Expr;
use SNOWGIRL_CORE\Exception\HTTP\MethodNotAllowed;

ini_set('max_execution_time', 0);
ini_set('memory_limit', '4096M');

/**
 * Class Admin
 * @package SNOWGIRL_CORE\Controller
 */
class Admin extends Controller
{
    /**
     * @throws Forbidden
     */
    public function initialize()
    {
        $this->app->services->logger->setName('admin')
            ->enable();

        parent::initialize();

        $this->app->seo->setNoIndexNoFollow();

        if (!$this->app->request->isAdminIp()) {
            throw new Forbidden;
        }

        $this->app->services->rdbms->debug(false);

        $this->app->services->mcms->disableSetOperation()
            ->disableGetOperation();

        $this->app->logRequest();

        if (!$this->app->request->getClient()->isLoggedIn() && !in_array($this->app->request->getAction(), ['login', 'add-user', 'site'])) {
            $this->app->request->redirectToRoute('admin', [
                'action' => 'login',
                'redirect_uri' => $this->app->request->getUri()
            ]);
        }

        if ($this->app->request->getClient()->isLoggedIn() && $this->app->request->getClient()->getUser()->isRole(User::ROLE_USER)) {
            throw new Forbidden;
        }

        $this->app->translator->setLocale('ru_RU');
    }

    public function actionSite()
    {
        $this->app->request->redirectToRoute('index');
    }

    protected function getDefaultAction()
    {
        $action = 'database';

        return $action;
    }

    public function actionIndex()
    {
        $this->app->request->redirectToRoute('admin', $this->getDefaultAction());
    }

    protected function getControlButtons()
    {
        return [
            [
                'text' => 'Sitemap',
                'icon' => 'refresh',
                'class' => 'info',
                'action' => 'generate-sitemap'
            ],
            [
                'text' => 'Rotate Cache',
                'icon' => 'refresh',
                'class' => 'warning',
                'action' => 'rotate-cache'
            ],
            [
                'text' => 'Rotate Sphinx',
                'icon' => 'refresh',
                'class' => 'default',
                'action' => 'rotate-sphinx'
            ],
        ];
    }

    /**
     * @throws Forbidden
     */
    public function actionControl()
    {
        if (!$this->app->request->getClient()->getUser()->isRole(User::ROLE_ADMIN, User::ROLE_MANAGER)) {
            throw new Forbidden;
        }

        $view = $this->app->views->getLayout(true);

        $view->setContentByTemplate('@snowgirl-core/admin/control.phtml', [
            'buttons' => $this->getControlButtons()
        ]);

        $this->app->response->setHTML(200, $view);
    }

    /**
     * @throws Forbidden
     */
    public function actionPhp()
    {
        if (!$this->app->request->getClient()->getUser()->isRole(User::ROLE_ADMIN)) {
            throw new Forbidden;
        }

        phpinfo();
    }

    /**
     * @return Response
     * @throws Forbidden
     */
    public function actionDatabase()
    {
        if (!$this->app->request->getClient()->getUser()->isRole(User::ROLE_ADMIN)) {
            throw new Forbidden;
        }

        $table = $this->getTable();
        $manager = $this->app->managers->getByTable($table)->clear();
        $entity = $manager->getEntity();

        $viewParams = [
            'table' => $table,
            'manager' => $manager,
            'searchBy' => $this->app->request->get('search_by', false),
            'searchValue' => $this->app->request->get('search_value', false),
            'orderBy' => $this->app->request->get('order_by', is_array($entity->getPk()) ? $entity->getPk()[0] : $entity->getPk()),
            'orderValue' => $this->app->request->get('order_value', 'desc'),
            'searchUseFulltext' => $this->app->request->get('search_use_fulltext', false)
        ];

        $isAjax = $this->app->request->isAjax();

        $pageNum = (int)$this->app->request->get('page', 1);
        $pageSize = (int)$this->app->request->get('size', 20);

        $makeCollection = function ($isLikeInsteadMatch = false) use ($viewParams, $manager, $pageNum, $pageSize, $isAjax) {
            $srcColumns = ['*'];
            $srcWhere = [];
            $srcOrder = [];

            if (mb_strlen($viewParams['searchBy']) && mb_strlen($viewParams['searchValue'])) {
                if ($viewParams['searchUseFulltext']) {
                    $db = $this->app->services->rdbms;

                    $query = $db->makeQuery($viewParams['searchValue'], $isLikeInsteadMatch);

                    if ($isLikeInsteadMatch) {
                        $srcWhere[] = new Expr($db->quote($viewParams['searchBy']) . ' LIKE ?', $query);
                    } else {
                        $tmp = 'MATCH(' . $db->quote($viewParams['searchBy']) . ') AGAINST (? IN BOOLEAN MODE)';

                        $srcColumns[] = new Expr($tmp . ' AS ' . $db->quote('relevance'), $query);
                        $srcWhere[] = new Expr($tmp, $query);
                        $srcOrder['relevance'] = SORT_DESC;
                    }
                } else {
                    $srcWhere[$viewParams['searchBy']] = $viewParams['searchValue'];
                }
            }

            if ($viewParams['orderBy'] && $viewParams['orderValue']) {
                $srcOrder[$viewParams['orderBy']] = [
                    'asc' => SORT_ASC,
                    'desc' => SORT_DESC
                ][$viewParams['orderValue']];
            }

            return $manager
                ->setColumns($srcColumns)
                ->setWhere($srcWhere)
                ->setOrders($srcOrder)
                ->setOffset(($pageNum - 1) * $pageSize)
                ->setLimit($pageSize)
                ->calcTotal(!$isAjax);
        };

        /** @var Manager $src */
        $src = $makeCollection(true);

        try {
            $viewParams['items'] = $src->getObjects();
        } catch (\Exception $ex) {
            $this->app->services->logger->makeException($ex, Logger::TYPE_WARN);

            if (Exception::_check($ex, 'Can\'t find FULLTEXT index matching the column list')) {
                $src = $makeCollection(true);
                $viewParams['items'] = $src->getObjects();
            } else {
                throw $ex;
            }
        }

        if ($isAjax) {
            $column = $this->app->request->get('column_display', $viewParams['searchBy'] ?: 'name');

            foreach ($viewParams['items'] as $k => $entity) {
                /** @var Entity $entity */

                $viewParams['items'][$k] = [
                    'id' => $entity->getId(),
                    'name' => $tmp2 = $entity->get($column),
                    'tokens' => preg_split("/[\s]+/", $tmp2)
                ];
            }

            return $this->app->response->setJSON(200, $viewParams['items']);
        }

        $entity = $this->app->managers->getByTable($this->getTable())->getEntity();

        $view = $this->app->views->getLayout(true);

        $view->setContentByTemplate('@snowgirl-core/admin/database.phtml', array_merge($viewParams, [
            'tables' => $this->getTables(),
            'forbiddenColumns' => $this->getForbiddenColumns(),
            'columns' => Arrays::removeKeys($entity->getColumns(), ['created_at', 'updated_at']),
            'pager' => $this->app->views->pager([
                'link' => $this->app->router->makeLink('admin', array_merge($this->app->request->getGetParams(), [
                    'action' => 'database',
                    'page' => '{page}'
                ])),
                'total' => $src->getTotal(),
                'size' => $pageSize,
                'page' => $pageNum
            ], $view)
        ]));

        return $this->app->response->setHTML(200, $view);
    }

    public function actionTranscript()
    {
        return $this->app->response->setJSON(200, Entity::normalizeUri($this->app->request->get('src')));
    }

    public function actionMd5()
    {
        return $this->app->response->setJSON(200, md5($this->app->request->get('src')));
    }

    /**
     * @return bool|Response
     * @throws Forbidden
     * @throws NotFound
     */
    public function actionRow()
    {
        if (!$this->app->request->getClient()->getUser()->isRole(User::ROLE_ADMIN)) {
            throw new Forbidden;
        }

        if ($this->app->request->isPatch() || $this->app->request->isPost()) {
            $manager = $this->app->managers->getByTable($this->getTable())->clear();

            if ($this->app->request->isPatch()) {
                if (!$id = $this->app->request->get('id')) {
                    throw (new BadRequest)->setInvalidParam('id');
                }

                if (is_array($manager->getEntity()->getPk())) {
                    $entity = $manager->selectByWhere($manager::makePkIdArrayById($id))[0];
                } else {
                    $entity = $manager->find($id);
                }

                if (!$entity || !$entity->getId()) {
                    throw new NotFound;
                }

                $input = $this->app->request->getStreamParams();
            } elseif ($this->app->request->isPost()) {
                $pk = $manager->getEntity()->getPk();

//                if (($id = $this->app->request->get('id')) || $id = $this->app->request->get($pk)) {
//                    if (is_array($pk)) {
//                        $entity = $manager->selectByWhere($manager::makePkIdArrayById($id))[0];
//                    } else {
//                        $entity = $manager->find($id);
//                    }
//                }

                if (is_array($pk)) {
                    $where = [];

                    foreach ($pk as $k) {
                        if ($v = $this->app->request->get($k)) {
                            $where[$k] = $v;
                        }
                    }

                    if (count($pk) == count($where)) {
                        $entity = $manager->setWhere($where)->getObject();
                    } else {
                        throw (new BadRequest)->setInvalidParam('pk');
                    }
                } else {
                    if (($id = $this->app->request->get('id')) || $id = $this->app->request->get($manager->getEntity()->getPk())) {
                        $entity = $manager->find($id);
                    }
                }

                if (!isset($entity) || !$entity || !$entity->getId()) {
                    $class = $manager->getEntity()->getClass();
                    $entity = new $class;
                }

                $input = $this->app->request->getPostParams();
            } else {
                $class = $manager->getEntity()->getClass();
                $entity = new $class;
            }

            foreach (array_keys($manager->getEntity()->getColumns()) as $column) {
                if (isset($input[$column]) && !in_array($column, $this->getForbiddenColumns())) {
                    $entity->set($column, $input[$column]);
                }
            }

            $this->app->services->rdbms->makeTransaction(function () use ($entity, $manager) {
                $manager->save($entity);
            });

            if ($this->app->request->isJSON() || $this->app->request->isAjax()) {
                return $this->app->response->setJSON(200, [
                    'data' => $entity->getAttrs(),
                    'id' => $entity->getId()
                ]);
            }

            return $this->app->request->redirect($this->app->request->getServer('HTTP_REFERER'));
        }

        if ($this->app->request->isDelete()) {
            if (!array_key_exists('id', $this->app->request->getParams())) {
                throw (new BadRequest)->setInvalidParam('id');
            }

            $manager = $this->app->managers->getByTable($this->getTable());
            $entity = $manager->getEntity();

            $id = $this->app->request->get('id');

            if (is_array($entity->getPk())) {
                $entity = $manager->selectByWhere($manager::makePkIdArrayById($id))[0];
            } else {
                $entity = $manager->find($id);
            }

            if (!$entity || !$entity->getId()) {
                throw new NotFound;
            }

            $this->app->services->rdbms->makeTransaction(function () use ($entity, $manager) {
                $manager->deleteOne($entity);
            });

            if ($this->app->request->isJSON() || $this->app->request->isAjax()) {
                return $this->app->response->setJSON(204);
            }

            return $this->app->request->redirect($this->app->request->getServer('HTTP_REFERER'));
        }

        throw (new MethodNotAllowed)->setValidMethod(['post', 'patch', 'delete']);
    }

    public function actionLogin()
    {
        if ($this->app->request->getClient()->isLoggedIn()) {
            $this->app->request->redirect($this->app->request->get('redirect_uri') ?: $this->app->router->makeLink('admin'));
        }

        $view = $this->app->views->getLayout(true);


        $content = $view->setContentByTemplate('@snowgirl-core/admin/login.phtml', [
            'redirect_uri' => $this->app->request->get('redirect_uri')
        ]);

        if ($this->app->request->isPost()) {
            try {
                if (!$login = $this->app->request->get('login')) {
                    throw (new BadRequest)->setInvalidParam('login');
                }

                if (!$password = $this->app->request->get('password')) {
                    throw (new BadRequest)->setInvalidParam('password');
                }

                if (!($user = $this->app->managers->users->setWhere(['login' => $login])->getObject())) {
                    throw new Exception('Такого пользователя нет в системе');
                }

                if ($user->getPassword() != md5($password)) {
                    throw new Exception('Не верный пароль');
                }

                $this->app->request->getClient()->logIn($user);
                $this->app->request->redirect($this->app->request->get('redirect_uri') ?: $this->app->router->makeLink('admin'));
            } catch (Exception $ex) {
                $this->app->services->logger->makeException($ex, Logger::TYPE_WARN);
                $view->addMessage($ex->getMessage(), Layout::MESSAGE_ERROR);

                $content->addParams([
                    'login' => $this->app->request->get('login'),
                    'password' => $this->app->request->get('password')
                ]);
            }
        }

        $this->app->response->setHTML(200, $view);
    }

    public function actionLogout()
    {
        if ($this->app->request->getClient()->isLoggedIn()) {
            $this->app->request->getClient()->logOut();
        }

        $this->app->request->redirect($this->app->request->getServer('HTTP_REFERER') ?: $this->app->router->makeLink('admin'));
    }

    /**
     * @throws Forbidden
     */
    public function actionGenerateSitemap()
    {
        if (!$this->app->request->getClient()->getUser()->isRole(User::ROLE_ADMIN, User::ROLE_MANAGER)) {
            throw new Forbidden;
        }

        self::_exec('Карта сайта успешно сгенерирована', function () {
            $this->app->seo->getSitemap()->update();
        });

        $this->app->request->redirectToRoute('admin', 'control');
    }

    /**
     * @throws Forbidden
     */
    public function actionRotateCache()
    {
        if (!$this->app->request->getClient()->getUser()->isRole(User::ROLE_ADMIN)) {
            throw new Forbidden;
        }

        self::_exec('Кэш успешно обновлен', function () {
            $this->app->services->mcms->rotate();
        });

        $this->app->request->redirectToRoute('admin', 'control');
    }

    /**
     * @throws Forbidden
     */
    public function actionRotateSphinx()
    {
        if (!$this->app->request->getClient()->getUser()->isRole(User::ROLE_ADMIN)) {
            throw new Forbidden;
        }

        self::_exec('Сфинкс успешно обновлен', function () {
            $this->app->utils->sphinx->doRotate();
        });

        $this->app->request->redirectToRoute('admin', 'control');
    }

    /**
     * @throws Forbidden
     */
    public function actionAddUser()
    {
        if (!$this->app->request->getClient()->getUser()->isRole(User::ROLE_ADMIN)) {
            throw new Forbidden;
        }

        self::_exec('Логин добавлен', function () {
            $this->app->services->rdbms->insertOne(User::getTable(), [
                'login' => $this->app->request->get('login'),
                'password' => md5($this->app->request->get('password')),
                'role' => $this->app->request->get('role')
            ]);
        });

        $this->app->request->redirectToRoute('admin', [
            'action' => 'login',
            'login' => $this->app->request->get('login'),
            'role' => $this->app->request->get('role')
        ]);
    }

    protected $tables;

    protected function getTables()
    {
        return $this->tables ?: $this->tables = $this->app->services->rdbms->getTables();
    }

    protected $table;

    protected function getTable()
    {
        return $this->table ?: $this->table = $this->app->request->get('table', current($this->getTables()));
    }

    protected function getForbiddenColumns()
    {
        return [$this->app->managers->getByTable($this->getTable())->getEntity()->getPk(), 'created_at', 'updated_at'];
    }

    protected function _exec($text = null, \Closure $fn, $isAjax = false, Layout $view = null)
    {
        try {
            $output = $fn();
            $text = null === $output ? ($text ?: 'Операция выполнена успешно') : $output;

            if ($isAjax) {
                $this->app->response->setHttpResponseCode(200)
                    ->setContentType('application/json');

                if (!$this->app->response->getBody()) {
                    $this->app->response->setBody(json_encode([
                        'ok' => 1,
                        'text' => $text
                    ]));
                }
            } else {
                $view = $view ?: $this->app->views->getLayout(true);
                $view->addMessage($text, Layout::MESSAGE_SUCCESS);
            }
        } catch (Exception $ex) {
            $this->app->services->logger->makeException($ex, Logger::TYPE_WARN);
            $output = false;

            if ($isAjax) {
                $this->app->response->setHttpResponseCode(200)
                    ->setContentType('application/json');

                if (!$this->app->response->getBody()) {
                    $this->app->response->setBody(json_encode([
                        'ok' => 0,
                        'text' => $ex->getMessage()
                    ]));
                }
            } else {
                $view = $view ?: $this->app->views->getLayout(true);
                $view->addMessage($ex->getMessage(), Layout::MESSAGE_ERROR);
            }
        }

        return $output;
    }

    /**
     * @return Response
     * @throws Forbidden
     */
    public function actionOptimizeImage()
    {
        if (!$this->app->request->getClient()->getUser()->isRole(User::ROLE_ADMIN)) {
            throw new Forbidden;
        }

        if (!$file = $this->app->request->get('file')) {
            throw (new BadRequest)->setInvalidParam('file');
        }

        return $this->app->response->setJSON(200, [
            'ok' => $this->app->images->get('dummy')->optimize($file) ? 1 : 0
        ]);
    }

    /**
     * @throws Forbidden
     */
    public function actionOptimizeWebJpg()
    {
        if (!$this->app->request->getClient()->getUser()->isRole(User::ROLE_ADMIN)) {
            throw new Forbidden;
        }

        $img = $this->app->images->get('dummy');

        $this->app->response->setContentType('text/html');

        foreach (glob($this->app->dirs['@web'] . '/img/*.jpg') as $image) {
            echo $img->optimize($image) ? '1' : '0';
            echo '<br/>';
        }
    }

    /**
     * @return Response
     * @throws Forbidden
     */
    public function actionDownloadImage()
    {
        if (!$this->app->request->getClient()->getUser()->isRole(User::ROLE_ADMIN)) {
            throw new Forbidden;
        }

        if (!$uri = $this->app->request->get('uri')) {
            throw (new BadRequest)->setInvalidParam('uri');
        }

        return $this->app->response->setJSON(200, [
            'ok' => $this->app->images->get('dummy')->optimize($uri) ? 1 : 0
        ]);
    }

    /**
     * @todo....
     */
    public function actionUploadImage()
    {
        if (!$this->app->request->getClient()->getUser()->isRole(User::ROLE_ADMIN, User::ROLE_MANAGER)) {
            throw new Forbidden;
        }
    }

    public function actionTagPicker()
    {
        if (!in_array($this->app->request->getMethod(), ['GET', 'POST'])) {
            throw (new MethodNotAllowed)->setValidMethod(['get', 'post']);
        }

        if (!$table = $this->app->request->get('table')) {
            throw (new BadRequest)->setInvalidParam('table');
        }

        $picker = $this->app->managers->getByTable($table)->makeTagPicker(
            $this->app->request->get('name'),
            $this->app->request->get('multiple'),
            $this->app->request->get('params')
        );

        $picker = (string)$picker;

        if ($this->app->request->isJSON()) {
            return $this->app->response->setJSON(200, ['view' => $picker]);
        } elseif ($this->app->request->isAjax()) {
            return $this->app->response->setHTML(200, $picker);
        } else {
            return $this->app->response->setHTML(200, $this->app->views->getLayout(true)->setContent($picker));
        }
    }

    /**
     * @todo...
     * @return Response
     */
    public function actionProfiler()
    {
        $this->app->request->redirect($this->app->profiler->getOption('host'));

        if (!$this->app->request->isGet()) {
            throw (new MethodNotAllowed)->setValidMethod('get');
        }

        $view = $this->app->views->getLayout(true);

        $view->setContentByTemplate('@snowgirl-core/admin/profiler.phtml', [
            'host' => $this->app->profiler->getOption('host')
        ]);

        return $this->app->response->setHTML(200, $view);
    }

    public function actionDisplay()
    {
        if (!$this->app->request->isGet()) {
            throw (new MethodNotAllowed)->setValidMethod('get');
        }

        if (!$addr = $this->app->request->get('addr')) {
            throw (new BadRequest)->setInvalidParam('addr');
        }

        $content = file_get_contents($addr);

//        $content = str_replace('<head>', '<head><base href="' . str_replace('http','https',$addr) . '"/>', $content);
        $content = str_replace('<head>', '<head><base href="' . $addr . '"/>', $content);

        return $this->app->response->setHTML(200, $content);
    }

    /**
     * @todo...
     * @return Response
     */
    public function actionImg()
    {
        if ($this->app->request->isPost()) {
            if (!$file = $this->app->request->getFileParam('file')) {
                throw (new BadRequest)->setInvalidParam('file');
            }

            if ($file = Image::downloadLocal($file, $error)) {
                return $this->app->response->setJSON(201, [
                    'hash' => $file,
                    'link' => $this->app->images->get($file)->getLink()
                ]);
            }
        } elseif ($this->app->request->isDelete()) {
            if (!$file = $this->app->request->get('file')) {
                throw (new BadRequest)->setInvalidParam('file');
            }

            if ($this->app->images->get($file)->delete($error)) {
                return $this->app->response->setJSON(204);
            }
        } else {
            throw (new MethodNotAllowed)->setValidMethod(['post', 'delete']);
        }

        return $this->app->response->setJSON(200, [
            'error' => $error
        ]);
    }

    /**
     * @throws Forbidden
     */
    public function actionPages()
    {
        if (!$this->app->request->getClient()->getUser()->isRole(User::ROLE_ADMIN, User::ROLE_MANAGER)) {
            throw new Forbidden;
        }

        $view = $this->app->views->getLayout(true);

        $view->setContentByTemplate('@snowgirl-core/admin/pages.phtml', [
            'pages' => $this->app->managers->pagesCustom->getObjects()
        ]);

        $this->app->response->setHTML(200, $view);
    }

    /**
     * @throws Forbidden
     */
    public function actionPage()
    {
        if (!$this->app->request->getClient()->getUser()->isRole(User::ROLE_ADMIN, User::ROLE_MANAGER)) {
            throw new Forbidden;
        }

        if (!$id = $this->app->request->get('id')) {
            throw (new BadRequest)->setInvalidParam('id');
        }

        if (!$page = $this->app->managers->pagesCustom->find($id)) {
            throw (new NotFound)->setNonExisting('page');
        }

        $view = $this->app->views->getLayout(true);

        $view->setContentByTemplate('@snowgirl-core/admin/page.phtml', [
            'page' => $page
        ]);

        $this->app->response->setHTML(200, $view);
    }
}
