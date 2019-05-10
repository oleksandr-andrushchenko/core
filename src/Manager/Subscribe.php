<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 12/21/17
 * Time: 8:02 AM
 */

namespace SNOWGIRL_CORE\Manager;

use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\Manager;
use SNOWGIRL_CORE\Entity\Subscribe as SubscribeEntity;
use SNOWGIRL_CORE\Helper\Data as DataHelper;

/**
 * Class Subscribe
 * @package SNOWGIRL_CORE\Manager
 */
class Subscribe extends Manager
{
    protected $masterServices = false;

    public function onInsert(Entity $entity)
    {
        /** @var SubscribeEntity $entity */

        $output = parent::onInsert($entity);

        $entity->setName(DataHelper::ucWords($entity->getName()));

        if (!$entity->issetAttr('code')) {
            $entity->setCode(md5($entity->getEmail() . time()));
        }

        if (!$entity->issetAttr('is_confirmed')) {
            $entity->setIsConfirmed(false);
        }

        if (!$entity->issetAttr('is_active')) {
            $entity->setIsActive(false);
        }

        return $output;
    }

    public function onInserted(Entity $entity)
    {
        /** @var SubscribeEntity $entity */

        $output = parent::onInserted($entity);

        $output = $output && $this->sendConfirmationEmail($entity);

        return $output;
    }

    /**
     * @param SubscribeEntity $subscribe
     * @return bool
     */
    public function sendConfirmationEmail(SubscribeEntity $subscribe)
    {
        return $this->app->views->subscribeEmail([
            'user' => $subscribe->getName(),
            'confLink' => $this->app->router->makeLink('default', ['action' => 'subscribe', 'code' => $subscribe->getCode()], 'master')
        ])->process($subscribe->getEmail());
    }

    /**
     * @param $email
     * @return SubscribeEntity
     */
    public function getByEmail($email)
    {
        return $this->clear()->setWhere(['email' => $email])->getObject();
    }

    /**
     * @param $code
     * @return SubscribeEntity
     */
    public function getByCode($code)
    {
        return $this->clear()->setWhere(['code' => $code])->getObject();
    }
}