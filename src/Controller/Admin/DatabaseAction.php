<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\Http\HttpApp as App;
use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\Helper\Arrays;
use SNOWGIRL_CORE\Manager;
use SNOWGIRL_CORE\Query\Expression;
use SNOWGIRL_CORE\RBAC;
use Throwable;

class DatabaseAction
{
    use PrepareServicesTrait;
    use DatabaseTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $app->rbac->checkPerm(RBAC::PERM_DATABASE_PAGE);

        $table = $this->getTable($app);
        $manager = $app->managers->getByTable($table)->clear();
        $entity = $manager->getEntity();

        $viewParams = [
            'table' => $table,
            'manager' => $manager,
            'searchBy' => $app->request->get('search_by', false),
            'searchValue' => $app->request->get('search_value', false),
            'orderBy' => $app->request->get('order_by', is_array($entity->getPk()) ? $entity->getPk()[0] : $entity->getPk()),
            'orderValue' => $app->request->get('order_value', 'desc'),
            'searchUseFulltext' => $app->request->get('search_use_fulltext', false)
        ];

        $isAjax = $app->request->isAjax();

        $pageNum = (int)$app->request->get('page', 1);
        $pageSize = (int)$app->request->get('size', 20);

        $makeCollection = function ($isLikeInsteadMatch = false) use ($app, $viewParams, $manager, $pageNum, $pageSize, $isAjax) {
            $srcColumns = ['*'];
            $srcWhere = [];
            $srcOrder = [];

            if (mb_strlen($viewParams['searchBy']) && mb_strlen($viewParams['searchValue'])) {
                if ($viewParams['searchUseFulltext']) {
                    $db = $app->container->db;

                    $query = $db->makeQuery($viewParams['searchValue'], $isLikeInsteadMatch);

                    if ($isLikeInsteadMatch) {
                        $srcWhere[] = new Expression($db->quote($viewParams['searchBy']) . ' LIKE ?', $query);
                    } else {
                        $tmp = 'MATCH(' . $db->quote($viewParams['searchBy']) . ') AGAINST (? IN BOOLEAN MODE)';

                        $srcColumns[] = new Expression($tmp . ' AS ' . $db->quote('relevance'), $query);
                        $srcWhere[] = new Expression($tmp, $query);
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
        } catch (Throwable $e) {
            $app->container->logger->warning($e);

            if (Exception::_check($e, 'Can\'t find FULLTEXT index matching the column list')) {
                $src = $makeCollection(true);
                $viewParams['items'] = $src->getObjects();
            } else {
                throw $e;
            }
        }

        if ($isAjax) {
            $column = $app->request->get('column_display', $viewParams['searchBy'] ?: 'name');

            foreach ($viewParams['items'] as $k => $entity) {
                /** @var Entity $entity */

                $viewParams['items'][$k] = [
                    'id' => $entity->getId(),
                    'name' => $tmp2 = $entity->get($column),
                    'tokens' => preg_split("/[\s]+/", $tmp2)
                ];
            }

            return $app->response->setJSON(200, $viewParams['items']);
        }

        $entity = $app->managers->getByTable($this->getTable($app))->getEntity();

        $view = $app->views->getLayout(true);

        $view->setContentByTemplate('@core/admin/database.phtml', array_merge($viewParams, [
            'tables' => $this->getTables($app),
            'forbiddenColumns' => $this->getForbiddenColumns($app),
            'columns' => Arrays::removeKeys($entity->getColumns(), ['created_at', 'updated_at']),
            'pager' => $app->views->pager([
                'link' => $app->router->makeLink('admin', array_merge($app->request->getGetParams(), [
                    'action' => 'database',
                    'page' => '{page}'
                ])),
                'total' => $src->getTotal(),
                'size' => $pageSize,
                'page' => $pageNum
            ], $view)
        ]));

        $app->response->setHTML(200, $view);
    }
}