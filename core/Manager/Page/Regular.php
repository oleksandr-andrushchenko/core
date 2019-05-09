<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 11/2/16
 * Time: 8:52 PM
 */
namespace SNOWGIRL_CORE\Manager\Page;

use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\Manager;
use SNOWGIRL_CORE\Entity\Page\Regular as PageRegularEntity;
use SNOWGIRL_CORE\Service\Storage\Query\Expr;

/**
 * Class Regular
 * @property PageRegularEntity $entity
 * @method static Regular factory()
 * @method Regular clear()
 * @method PageRegularEntity[] getObjects($idAsKeyOrKey = null)
 * @method PageRegularEntity getObject()
 * @package SNOWGIRL_CORE\Manager\Page
 */
class Regular extends Manager
{
    protected $masterServices = false;

    protected function onUpdated(Entity $entity)
    {
        /** @var PageRegularEntity $entity */

        $output = parent::onUpdated($entity);
        $output = $output && $this->deleteCacheByKey($entity);
        return $output;
    }

    public function getItemCacheKey($key)
    {
        return $this->entity->getTable() . '-' . $key;
    }

    protected function deleteCacheByKey(PageRegularEntity $entity)
    {
        return $this->app->services->mcms->delete($this->getItemCacheKey($entity->getKey()));
    }

    public function onDeleted(Entity $entity)
    {
        /** @var PageRegularEntity $entity */
        $output = parent::onDeleted($entity);
        $output = $output && $this->deleteCacheByKey($entity);
        return $output;
    }

    /**
     * @param $key
     * @return PageRegularEntity
     */
    public function findByKey($key)
    {
        if ($this->menu && isset($this->menu[$key])) {
            return $this->menu[$key];
        }

        return $this->app->services->mcms->call($this->getItemCacheKey($key), function () use ($key) {
            $tmp = $this->clear()->setWhere(['key' => $key])->getObject();
            return $tmp ?: new PageRegularEntity();
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
     * @return PageRegularEntity[]
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
        /** @var PageRegularEntity $entity */

        $key = $entity->getKey();

        if ('index' == $key) {
            return $this->app->router->makeLink('index', $params, $domain);
        }

        $params['action'] = $key;

        return $this->app->router->makeLink('default', $params, $domain);
    }
}