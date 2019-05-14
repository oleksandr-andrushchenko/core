<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 5/10/19
 * Time: 10:18 PM
 */

namespace SNOWGIRL_CORE\Controller\Admin;

use SNOWGIRL_CORE\App\Web as App;
use SNOWGIRL_CORE\Entity\User;
use SNOWGIRL_CORE\Exception\HTTP\BadRequest;
use SNOWGIRL_CORE\Exception\HTTP\Forbidden;
use SNOWGIRL_CORE\Exception\HTTP\MethodNotAllowed;
use SNOWGIRL_CORE\Exception\HTTP\NotFound;

class RowAction
{
    use PrepareServicesTrait;
    use DatabaseTrait;

    /**
     * @param App $app
     *
     * @return bool|\SNOWGIRL_CORE\Response
     * @throws Forbidden
     * @throws NotFound
     */
    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        if (!$app->request->getClient()->getUser()->isRole(User::ROLE_ADMIN)) {
            throw new Forbidden;
        }

        if ($app->request->isPatch() || $app->request->isPost()) {
            $manager = $app->managers->getByTable($this->getTable($app))->clear();

            if ($app->request->isPatch()) {
                if (!$id = $app->request->get('id')) {
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

                $input = $app->request->getStreamParams();
            } elseif ($app->request->isPost()) {
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
                        throw (new BadRequest)->setInvalidParam('pk');
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
            } else {
                $class = $manager->getEntity()->getClass();
                $entity = new $class;
            }

            foreach (array_keys($manager->getEntity()->getColumns()) as $column) {
                if (isset($input[$column]) && !in_array($column, $this->getForbiddenColumns($app))) {
                    $entity->set($column, $input[$column]);
                }
            }

            $app->services->rdbms->makeTransaction(function () use ($entity, $manager) {
                $manager->save($entity);
            });

            if ($app->request->isJSON() || $app->request->isAjax()) {
                return $app->response->setJSON(200, [
                    'data' => $entity->getAttrs(),
                    'id' => $entity->getId()
                ]);
            }

            return $app->request->redirect($app->request->getServer('HTTP_REFERER'));
        }

        if ($app->request->isDelete()) {
            if (!array_key_exists('id', $app->request->getParams())) {
                throw (new BadRequest)->setInvalidParam('id');
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
                throw new NotFound;
            }

            $app->services->rdbms->makeTransaction(function () use ($entity, $manager) {
                $manager->deleteOne($entity);
            });

            if ($app->request->isJSON() || $app->request->isAjax()) {
                return $app->response->setJSON(204);
            }

            return $app->request->redirect($app->request->getServer('HTTP_REFERER'));
        }

        throw (new MethodNotAllowed)->setValidMethod(['post', 'patch', 'delete']);
    }
}