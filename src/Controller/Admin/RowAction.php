<?php

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\Http\HttpApp as App;
use SNOWGIRL_CORE\Http\Exception\BadRequestHttpException;
use SNOWGIRL_CORE\Http\Exception\MethodNotAllowedHttpException;
use SNOWGIRL_CORE\Http\Exception\NotFoundHttpException;
use SNOWGIRL_CORE\RBAC;

class RowAction
{
    use PrepareServicesTrait;
    use DatabaseTrait;

    /**
     * @param App $app
     *
     * @return bool|\SNOWGIRL_CORE\Response
     * @throws NotFoundHttpException
     */
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        if ($app->request->isPatch() || $app->request->isPost()) {
            $manager = $app->managers->getByTable($this->getTable($app))->clear();

            if ($app->request->isPatch()) {
                $app->rbac->checkPerm(RBAC::PERM_UPDATE_ROW);

                if (!$id = $app->request->get('id')) {
                    throw (new BadRequestHttpException)->setInvalidParam('id');
                }

                if (is_array($manager->getEntity()->getPk())) {
                    $entity = $manager->selectByWhere($manager::makePkIdArrayById($id))[0];
                } else {
                    $entity = $manager->find($id);
                }

                if (!$entity || !$entity->getId()) {
                    throw new NotFoundHttpException;
                }

                $input = $app->request->getStreamParams();
            } elseif ($app->request->isPost()) {
                $app->rbac->checkPerm(RBAC::PERM_CREATE_ROW);

                $pk = $manager->getEntity()->getPk();

//                if (($id = $app->request->get('id')) || $id = $app->request->get($pk)) {
//                    if (is_array($pk)) {
//                        $entity = $manager->selectByWhere($manager::makePkIdArrayById($id))[0];
//                    } else {
//                        $entity = $manager->find($id);
//                    }
//                }

                if (is_array($pk)) {
                    $where = [];

                    foreach ($pk as $k) {
                        if ($v = $app->request->get($k)) {
                            $where[$k] = $v;
                        }
                    }

                    if (count($pk) == count($where)) {
                        $entity = $manager->setWhere($where)->getObject();
                    } else {
                        throw (new BadRequestHttpException)->setInvalidParam('pk');
                    }
                } else {
                    if (($id = $app->request->get('id')) || $id = $app->request->get($manager->getEntity()->getPk())) {
                        $entity = $manager->find($id);
                    }
                }

                if (!isset($entity) || !$entity || !$entity->getId()) {
                    $class = $manager->getEntity()->getClass();
                    $entity = new $class;
                }

                $input = $app->request->getPostParams();
            }

            foreach (array_keys($manager->getEntity()->getColumns()) as $column) {
                if (isset($input[$column]) && !in_array($column, $this->getForbiddenColumns($app))) {
                    $entity->set($column, $input[$column]);
                }
            }

            $app->container->db->makeTransaction(function () use ($entity, $manager) {
                $manager->save($entity);
            });

            if ($app->request->isJSON() || $app->request->isAjax()) {
                return $app->response->setJSON(200, [
                    'data' => $entity->getAttrs(),
                    'id' => $entity->getId()
                ]);
            }

            return $app->request->redirectBack();
        }

        if ($app->request->isDelete()) {
            $app->rbac->checkPerm(RBAC::PERM_DELETE_ROW);

            if (!array_key_exists('id', $app->request->getParams())) {
                throw (new BadRequestHttpException)->setInvalidParam('id');
            }

            $manager = $app->managers->getByTable($this->getTable($app));
            $entity = $manager->getEntity();

            $id = $app->request->get('id');

            if (is_array($entity->getPk())) {
                $entity = $manager->selectByWhere($manager::makePkIdArrayById($id))[0];
            } else {
                $entity = $manager->find($id);
            }

            if (!$entity || !$entity->getId()) {
                throw new NotFoundHttpException;
            }

            $app->container->db->makeTransaction(function () use ($entity, $manager) {
                $manager->deleteOne($entity);
            });

            if ($app->request->isJSON() || $app->request->isAjax()) {
                return $app->response->setJSON(204);
            }

            return $app->request->redirectBack();
        }

        throw (new MethodNotAllowedHttpException)->setValidMethod(['post', 'patch', 'delete']);
    }
}