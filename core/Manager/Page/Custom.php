<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 2/2/18
 * Time: 12:32 AM
 */
namespace SNOWGIRL_CORE\Manager\Page;

use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\Manager;
use SNOWGIRL_CORE\Entity\Page\Custom as PageCustomEntity;
use SNOWGIRL_CORE\Entity\Redirect;

/**
 * Class Custom
 * @method PageCustomEntity[] getObjects($idAsKeyOrKey = null)
 * @package SNOWGIRL_CORE\Manager\Page
 */
class Custom extends Manager
{
    public const CACHE_URI_HASHES = 'pages-uri-hashes';

    protected $masterServices = false;

    protected function onInsert(Entity $entity)
    {
        /** @var PageCustomEntity $entity */

        $output = parent::onInsert($entity);

        //@todo check URI uniqueness between tables...

        if (!$entity->issetAttr('uri_hash')) {
            $entity->set('uri_hash', md5($entity->getUri()));
        }

        return $output;
    }

    protected function onUpdate(Entity $entity)
    {
        /** @var PageCustomEntity $entity */

        $output = parent::onUpdate($entity);

        //@todo check URI uniqueness between tables...

        if ($entity->isAttrChanged('uri')) {
            $entity->set('uri_hash', md5($entity->getUri()));
        }

        return $output;
    }

    protected function onUpdated(Entity $entity)
    {
        /** @var PageCustomEntity $entity */

        $output = parent::onUpdated($entity);

        if ($entity->isAttrChanged('uri')) {
            $output = $output && $this->app->managers->redirects->save((new Redirect)
                    ->setUriFrom($entity->getPrevAttr('uri'))
                    ->setUriTo($entity->getUri()));
        }

        return $output;
    }

    /**
     * @param $uri
     * @return PageCustomEntity
     */
    public function findActiveByUri($uri)
    {
        $uri = trim($uri, '/');

        $hashes = array_map(function ($row) {
            return $row['uri_hash'];
        }, $this->copy(true)
            ->setColumns('uri_hash')
            ->setWhere(['is_active' => 1])
            ->cacheOutput(self::CACHE_URI_HASHES)
            ->getArrays());

        $hash = md5($uri);

        if (!in_array($hash, $hashes)) {
            return null;
        }

        return $this->findBy('uri_hash', $hash);
    }

    public function getLink(Entity $entity, array $params = [], $domain = false)
    {
        /** @var PageCustomEntity $entity */
        $params['action'] = $entity->getUri();
        return $this->app->router->makeLink('default', $params, $domain);
    }

    public function getAdminLink(PageCustomEntity $entity, array $params = [])
    {
        return $this->app->router->makeLink('admin', array_merge($params, [
            'action' => 'page',
            'id' => $entity->getId()
        ]));
    }
}