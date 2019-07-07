<?php

namespace SNOWGIRL_CORE\Manager;

use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\Manager;
use SNOWGIRL_CORE\Entity\Page as PageEntity;
use SNOWGIRL_CORE\Service\Storage\Query\Expr;
use SNOWGIRL_CORE\Entity\Redirect;

class Page extends Manager
{
    public const CACHE_URI_HASHES = 'pages-uri-hashes';

    protected $masterServices = false;

    protected function onUpdated(Entity $entity)
    {
        /** @var PageEntity $entity */

        $output = parent::onUpdated($entity);

        if ($entity->hasAttr('uri') && $entity->isAttrChanged('uri')) {
            $output = $output && $this->app->managers->redirects->save((new Redirect)
                    ->setUriFrom($entity->getPrevAttr('uri'))
                    ->setUriTo($entity->getUri()));
        }

        $output = $output && $this->deleteCacheByKey($entity);

        return $output;
    }

    public function getItemCacheKey($key)
    {
        return $this->entity->getTable() . '-' . $key;
    }

    protected function deleteCacheByKey(PageEntity $entity)
    {
        return $this->app->services->mcms->delete($this->getItemCacheKey($entity->getKey()));
    }

    public function onDeleted(Entity $entity)
    {
        /** @var PageEntity $entity */
        $output = parent::onDeleted($entity);
        $output = $output && $this->deleteCacheByKey($entity);
        return $output;
    }

    /**
     * @param $key
     *
     * @return PageEntity
     */
    public function findByKey($key)
    {
        if ($this->menu && isset($this->menu[$key])) {
            return $this->menu[$key];
        }

        return $this->app->services->mcms->call($this->getItemCacheKey($key), function () use ($key) {
            $tmp = $this->clear()->setWhere(['key' => $key])->getObject();
            return $tmp ?: new PageEntity();
        });
    }

    protected $menu;

    public function getMenuCacheKey()
    {
        return implode('-', [
            $this->entity->getTable(),
            'menu'
        ]);
    }

    /**
     * @return PageEntity[]
     */
    public function getMenu()
    {
        if (null === $this->menu) {
            $this->menu = $this->clear()
                ->setWhere(['is_menu' => 1, new Expr($this->app->services->rdbms->quote('menu_title') . ' IS NOT NULL')])
                ->setOrders(['rating' => SORT_DESC])
                ->cacheOutput($this->getMenuCacheKey())
                ->getObjects('key');
        }

        return $this->menu;
    }

    public function getLink(Entity $entity, array $params = [], $domain = false)
    {
        /** @var PageEntity $entity */

        if ($uri = $entity->getUri()) {
            $params['action'] = $uri;

            return $this->app->router->makeLink('default', $params, $domain);
        }

        $key = $entity->getKey();

        if ('index' == $key) {
            return $this->app->router->makeLink('index', $params, $domain);
        }

        $params['action'] = $key;

        return $this->app->router->makeLink('default', $params, $domain);
    }

    protected function onInsert(Entity $entity)
    {
        /** @var PageEntity $entity */

        $output = parent::onInsert($entity);

        //@todo check URI uniqueness between tables...

        if (!$entity->issetAttr('uri_hash')) {
            $entity->set('uri_hash', md5($entity->getUri()));
        }

        return $output;
    }

    protected function onUpdate(Entity $entity)
    {
        /** @var PageEntity $entity */

        $output = parent::onUpdate($entity);

        //@todo check URI uniqueness between tables...

        if ($entity->hasAttr('uri') && $entity->isAttrChanged('uri')) {
            $entity->set('uri_hash', md5($entity->getUri()));
        }

        return $output;
    }

    /**
     * @param $uri
     *
     * @return PageEntity
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

    public function getAdminLink(PageEntity $entity, array $params = [])
    {
        return $this->app->router->makeLink('admin', array_merge($params, [
            'action' => 'page',
            'id' => $entity->getId()
        ]));
    }
}